/* 
 * File:   fileutil.h
 * Author: cross
 *
 * Created on July 7, 2010, 3:08 PM
 */

#ifndef _FILEUTIL_H
#define	_FILEUTIL_H

#include <vector>
#include <string>
#include "stringfunctions.h"
#include "defs.h"
#include <sys/stat.h>
#if defined(_MSC_VER) && _MSC_VER < 1900
#define stat _stat
#endif


FILE* fopendebug(const char* fileName, const char* flag);
int fclosedebug(FILE* stream);

#define fileopen fopen
#define fileclose fclose

char* readFile(char* fileName);
int writeFile(const std::string& fileName, const std::string& text, bool append);

/*! \brief return all the directories based on the dir argument
* usage: 
*      std::vector<std::string> directories;
*      enumerateDirectories(".", directories);
*      ...
*/
int enumerateDirectories (const char* dir, std::vector<char*> &directories);
/*! \brief return all the files matching the dir and extension
* usage: 
*      std::vector<std::string> files;
*      enumerateFiles(".", "txt", files);
*      ...
*/
int enumerateFiles (const char* dir, const char* extension, std::vector<char*> &files);

/*! \brief implementation of the ftw.h that works on windows and *nix systems
*/
int FileTreeWalk (const char* dir, int (*fn) (const char *fpath, const struct stat* st));

djondb::string getCurrentDir();
bool existFile(const char* fileName);
bool existDir(const char* dir);
bool makeDir(const char* dir);
bool checkFileCreation(const char* dir);
bool removeFile(const char* file);
bool removeDirectory(const char* path);
__int64 fileSize(const char* file);
long pageSize();

//! This method combines two paths into one
/*! it'll add the file separator char 
  if not present in the first path, also it will check if one of the paths is NULL to avoid
  wrong concatenation. The caller should call free on the result.
  \param path A null terminated string, it could be NULL
  \param path2 A null terminated string, it could be NULL
*/
char* combinePath(const char* path, const char* path2);

//! This solves a problem when a file path is read from linux and was produced for windows DBs or viceversa
/*! Replaces all the \\ in a linux system for / and / for \\ on windows systems.
 */
void fixFileSeparator(char* path);

#ifndef WINDOWS
#define FILESEPARATOR "/"
#else
#define FILESEPARATOR (const char*)"\\"
#endif

#endif	/* _FILEUTIL_H */

