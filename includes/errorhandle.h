#ifndef ERRORHANDLE_H
#define ERRORHANDLE_H

/******************************************************************
Error Functions
*/

bool checkError();

void setLastError(int errorCode, const char* errorDescription, ...);
bool errorOcurred();
const char* lastErrorDescription();
int lastErrorCode();
void clearError();


// last error 7
/******************************************************************/

#endif // ERRORHANDLE_H
