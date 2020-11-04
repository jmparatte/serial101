//éèà


#define __PROG__ "serial101"


// -----------------------------------------------------------------------------


#define OK true
#define ER false

#define _OK_ "=OK="
#define _ER_ "#ER#"


// -----------------------------------------------------------------------------


#ifndef LED_BUILTIN
#define LED_BUILTIN 2 // ESP32 DEVKIT V1
//#define LED_BUILTIN 25 // ESP32 TTGO, HELTEC
#endif


// -----------------------------------------------------------------------------


#include <Wire.h>


// -----------------------------------------------------------------------------


#if true
	#include "jm_LCM2004A_I2C_U00FF.h"
	jm_LCM2004A_I2C_U00FF lcd;
#else
	#include <jm_LCM2004A_I2C.h>
	jm_LCM2004A_I2C lcd;
#endif


// -----------------------------------------------------------------------------


constexpr int FACTORIAL(int n)
{
	return n <= 1 ? 1 : (n * FACTORIAL(n - 1));
}


constexpr int STRSUM(const char * str, size_t idx=0, int sum=0)
{
	return (idx >= strlen(str) ? sum : str[idx] + STRSUM(str, idx+1, sum));
}


// -----------------------------------------------------------------------------


/*	REST API - PARSING - EXECUTION
	==============================

	REpresentational State Transfer
	Application Programming Interface

	*/


bool rest_parsing_errored = false;


String rest_terms_string[16];
byte rest_terms_hash[16];
long rest_terms_long[16];
int rest_terms_count = 0;


String rest_term_string = "";
byte rest_term_hash = 0;
bool rest_term_isnumeric = false;
long rest_term_long = 0;
bool rest_term_started = false;


void rest_term_reset()
{
	rest_term_string = "";
	rest_term_hash = 0;
	rest_term_isnumeric = false;
	rest_term_long = 0;
	rest_term_started = false;
}


void rest_term_push()
{
	rest_terms_string[rest_terms_count] = rest_term_string;
	rest_terms_hash[rest_terms_count] = rest_term_hash;
	rest_terms_long[rest_terms_count] = rest_term_long;

	rest_terms_count++;

	rest_term_reset();
}


void rest_parsing_reset()
{
	rest_parsing_errored = false;

	rest_terms_count = 0;

	rest_term_reset();
}


bool rest_parsing_cycle(byte key) // return EOL
{
	if (key=='\0') // reset REST parsing ?
	{
		rest_parsing_reset();
	}
	else // REST parsing continue.
	//
	if (key=='\r' || key=='\n') // EOL ?
	{
		if (!rest_parsing_errored && rest_term_started) rest_term_push();
		//
		return true; // return EOL.
	}
	else // not EOL...
	//
	if (rest_parsing_errored) // REST parsing errored ?
	{
		// nothing to do with REST parsing errored.
	}
	else // REST parsing not errored.
	//
	if (!rest_term_started) // REST term parsing not started ?
	{
		if (key=='/') // start REST term parsing ?
		{
			rest_term_started = true;
		}
		else
		{
		 	rest_parsing_errored = true;
		}
	}
	else // REST term parsing already started ?
	//
	if (key=='/') // end of REST term (next REST term) ?
	{
		rest_term_push();

		rest_term_started = true; // start a new term.
	}
	else // not end of REST term.
	//
	// append char to REST term...
	{
		// concat char to term...
		rest_term_string.concat((char)key);

		// update hash term...
		rest_term_hash += key;

		if (rest_term_isnumeric) // is term already numeric ?
		{
			// append new digit...
			rest_term_long *= 10;
			rest_term_long += (key-'0');
		}
		else // term is not numeric.
		//
		if (rest_term_string.length()==1 && key>='0' && key<='9') // is starting numeric ?
		{
			// switch to numeric term parsing.
			rest_term_isnumeric = true;

			rest_term_long = (key - '0');
		}
		else // term is already not numeric.
		//
		{
			// alpha term, nothing to do...
		}
	}

	return false; // return not EOL
}


// -----------------------------------------------------------------------------


bool rest_parsing_exec() // return OK
{
	if (rest_parsing_errored) return ER;

	if (rest_terms_count==0) return OK;

	// REST command interpreter.

	int i;

	switch(rest_terms_hash[0]) {

		case byte(STRSUM("")):
		case byte(STRSUM("index.html")):
		case byte(STRSUM("index.php")):

			Serial.print(F("<?php $__PROG__=\""));
			Serial.print(__PROG__);
			Serial.print(F("\"; $millis="));
			Serial.print(millis());
			Serial.print(F("; include(\"serial101-html1.php\"); ?>\n"));

			break;

		case byte(STRSUM("ms")): // /millis
		case byte(STRSUM("millis")): // /millis

			if (rest_terms_count==1) {
				Serial.println(millis());
			}
			else {
				return ER;
			}
			break;

		case byte(STRSUM("d")): // /digital/<pin> | /digital/<pin>/<state>
		case byte(STRSUM("digital")): // /digital/<pin> | /digital/<pin>/<state>

			if (rest_terms_hash[1]==byte(STRSUM("L")) ||
				rest_terms_hash[1]==byte(STRSUM("LED")) ||
				rest_terms_hash[1]==byte(STRSUM("LED_BUILTIN"))) rest_terms_long[1] = LED_BUILTIN;

			if (rest_terms_count==2) {
				Serial.println(digitalRead(rest_terms_long[1]));
			}
			else
			if (rest_terms_count==3) {
				digitalWrite(rest_terms_long[1], rest_terms_long[2]);
			}
			else {
				return ER;
			}
			break;

		case byte(STRSUM("tone")): // /tone/<pin>/<freq>/<millis>

			if (rest_terms_count==4) {
			#ifdef __AVR__
				tone(rest_terms_long[1], rest_terms_long[2], rest_terms_long[3]);
			#endif
			}
			else {
				return ER;
			}
			break;

		case byte(STRSUM("lcd")): // /lcd/cls/cur/1/3/ijkl/cur/3/1/mnop

			bool is_writing_text;
			is_writing_text = false; // when writing texts, insert '/' between texts
			i = 1;
			while(i<rest_terms_count) {

				switch(rest_terms_hash[i]) {

					case byte(STRSUM("cls")): // /cls
						i++;
						lcd.clear_display();
						is_writing_text = false;
						break;

					case byte(STRSUM("cur")): // /cur/<col>/<row>
						if ((i+3)>rest_terms_count) break;
						i++;
						lcd.set_cursor(rest_terms_long[i+0], rest_terms_long[i+1]);
						i++;
						i++;
						is_writing_text = false;
						break;

					default: // /<text>[/[...]]
						if (is_writing_text) lcd.write('/');
						lcd.print(rest_terms_string[i]);
						i++;
						is_writing_text = true;
						break;
				}
			}
			if (i==rest_terms_count)
				{}
			else
				return ER;
			break;

		default: // unknowed command

			return ER;
	}

	return OK;
}


// -----------------------------------------------------------------------------


void setup()
{
	Serial.begin(115200);					// Serial baudrate=115200, databits=8, parity=None, stopbits=1
	while (!Serial && millis()<3000) {}		// wait for USB Serial ready maximum 3s
	while (Serial.read()!=-1) {}			// flush Serial RX
	Serial.println(F(__PROG__));			// display program signature

	Wire.begin();							// begin Wire (I2C bus)

	lcd.begin();							// begin lcd (LCM2004A)

	lcd.clear_display();					// clear_display & home
	lcd.print(F(__PROG__));					// print a splash screen
	lcd.print(F("..."));					// ...

	pinMode(LED_BUILTIN, OUTPUT);			// just for playing with built-in LED

	rest_parsing_reset();					// reset to parse 1st REST command
}


void loop()
{
	int c = Serial.read();					// RX char or none.
	// check...
	if (c!=-1)								// RX char ?
	//then
	if (rest_parsing_cycle((byte) c))		// parse REST char => EOL ?
	{
		if (rest_parsing_exec())			// exec REST line => OK ?
		//then
			Serial.println(_OK_);			// TX success status.
		else
			Serial.println(_ER_);			// TX error status.

		rest_parsing_reset();
	}
}
