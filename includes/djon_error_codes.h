/*
 * =====================================================================================
 *
 *       Filename:  djon_error_codes.h
 *
 *    Description:  A place to put all the error codes
 *
 *        Version:  1.0
 *        Created:  03/07/2013 10:17:01 PM
 *       Revision:  none
 *       Compiler:  gcc
 *
 *         Author:  Juan Pablo Crossley (Cross), crossleyjuan@gmail.com
 *   Organization:  djondb
 *
 * This file is part of the djondb project, for license information please refer to the LICENSE file,
 * the application and libraries are provided as-is and free of use under the terms explained in the file LICENSE
 * Its authors create this application in order to make the world a better place to live, but you should use it on
 * your own risks.
 * 
 * Also, be adviced that, the GPL license force the committers to ensure this application will be free of use, thus
 * if you do any modification you will be required to provide it for free unless you use it for personal use (you may 
 * charge yourself if you want), bare in mind that you will be required to provide a copy of the license terms that ensures
 * this program will be open sourced and all its derivated work will be too.
 * =====================================================================================
 */ 
#ifndef DJON_ERROR_CODES_INCLUDED_H
#define DJON_ERROR_CODES_INCLUDED_H 

// 100 is reserved for database errors
const int D_ERROR_TOO_MANY_RESULTS = 100;
const int D_ERROR_PARSEERROR = 101;
const int D_ERROR_NOTIMPLEMENTED = 102; //!< Elements not supported yet

// 200 is reserved for system errors, these errors should be checked in development if reported
const int D_ERROR_UNKNOWN = 200; //!< Reserved for null pointers
const int D_ERROR_SYSTEMERROR = 201; //!< Reserved for any system error that is not related with the DB

// 300 network errors
const int D_ERROR_NET_UNKNOWN_DATA = 300; //!< Used when there's garbage in the network connection

// 400 Internal errors these errors could be used as control points
const int D_ERROR_UNKNOWN_TYPE = 400;
const int D_ERROR_UNKNOWN_COMMAND = 401;

// 600 is reserved for user errors
const int D_ERROR_CONNECTION = 600;
const int D_ERROR_INVALID_STATEMENT = 601;
const int D_ERROR_MISSING_PARAMETERS = 602;
const int D_ERROR_INSERT_FAILED = 603;
const int D_ERROR_UPDATE_FAILED = 604;
const int D_ERROR_REMOVE_FAILED = 605;
const int D_ERROR_CURSOR_INVALID_STATUS = 606;


// 10000 reserved for errors in the driver
const int D_ERROR_INVALIDTRANSACTION_STATE = 10001;

#endif /* DJON_ERROR_CODES_INCLUDED_H */
