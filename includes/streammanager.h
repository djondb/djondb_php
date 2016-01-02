/*
 * =====================================================================================
 *
 *       Filename:  StreamManager.h
 *
 *    Description:  
 *
 *        Version:  1.0
 *        Created:  08/11/2012 11:08:36 AM
 *       Revision:  none
 *       Compiler:  gcc
 *
 *         Author:  YOUR NAME (), 
 *   Organization:  
 *
 * =====================================================================================
 */

#ifndef STREAMMANAGER_INCLUDE_H
#define STREAMMANAGER_INCLUDE_H

#include "dbfilestream.h"
#include "util.h"
#include <map>
#include <string>
#include <vector>

enum FILE_TYPE {
    DATA_FTYPE,
    STRC_FTYPE,
    INDEX_FTYPE
};

typedef DBFileStream StreamType;
typedef std::map<FILE_TYPE, StreamType*> MapStream;
struct NS {
	std::string ns;
	MapStream* streams;
};

typedef std::map<std::string, NS, bool(*)(std::string, std::string)> MapNamespace;
typedef std::map<std::string, MapNamespace*, bool(*)(std::string, std::string) > MapDb;

class StreamManager {
	public:
		StreamManager();
		virtual ~StreamManager();

		static StreamManager* getStreamManager();
		StreamType* open(const char* db, const char* ns, FILE_TYPE type);
		  std::vector<std::string>* dbs() const;
		  std::vector<std::string>* namespaces(const char* db) const;
		void saveDatabases();
		void shutdown();
		bool dropNamespace(const char* db, const char* ns);
		void setDataDir(const std::string& dataDir);

		void setInitializing(bool initializing);

	private:
		bool close(const char* db, const char* ns);
		StreamType* checkVersion(StreamType* stream);

	private:
		MapDb _spaces;
		std::string fileName(std::string ns, FILE_TYPE type) const;

		std::string _dataDir;

		static StreamManager* _manager;
		Logger* _logger;
		bool _initializing;
};

#endif // STREAMMANAGER_INCLUDE_H
