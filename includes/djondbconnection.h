#ifndef DJONDB_CONNECTION_H
#define DJONDB_CONNECTION_H

#include <string>
#include <vector>
#include "bson.h"
#include "util.h"
#include "filterparser.h"

#ifdef WINDOWS
#ifdef WIN_CLIENT
   #define LibraryExport   __declspec( dllimport )
#else
   #define LibraryExport   __declspec( dllexport )
#endif
#else
   #define LibraryExport
#endif

class NetworkOutputStream;
class NetworkInputStream;
class CommandWriter;
class TransactionManager;
class Command;

#define SERVER_PORT 1243

namespace djondb {

	class DjondbCursor;

	class LibraryExport DjondbConnection
	{
		public:
			/** Default constructor */
			DjondbConnection(const char* host);
			DjondbConnection(const char* host, int port);
			DjondbConnection(const DjondbConnection& orig);

			/** Default destructor */
			virtual ~DjondbConnection();

			const char* beginTransaction();
			void commitTransaction();
			void rollbackTransaction();

			bool open();
			void close();
			void internalClose();
			bool isOpen() const;

			bool shutdown() const;

			bool insert(const char* db, const char* ns, const char* json);
			bool insert(const char* db, const char* ns, const BSONObj& obj);
			BSONObj* findByKey(const char* db, const char* ns, const char* select, const char* id);
			BSONObj* findByKey(const char* db, const char* ns, const char* id);
			DjondbCursor* find(const char* db, const char* ns);
			DjondbCursor* find(const char* db, const char* ns, const BSONObj& options);
			DjondbCursor* find(const char* db, const char* ns, const char* filter);
			DjondbCursor* find(const char* db, const char* ns, const char* filter, const BSONObj& options);
			DjondbCursor* find(const char* db, const char* ns, const char* select, const char* filter);
			DjondbCursor* find(const char* db, const char* ns, const char* select, const char* filter, const BSONObj& options);
			bool update(const char* db, const char* ns, const char* json);
			bool update(const char* db, const char* ns, const BSONObj& bson);
			bool remove(const char* db, const char* ns, const char* id, const char* revision);
			DjondbCursor* executeQuery(const char* query);
			bool executeUpdate(const char* query);
			void releaseCursor(DjondbCursor* cursor);
			void createIndex(const BSONObj& indexDef);

			bool dropNamespace(const char* db, const char* ns);
			std::vector<std::string>* dbs() const;
			std::vector<std::string>* namespaces(const char* db) const;

			char* host() const;

			int backup(const char* db, const char* destFile) const;
			int backup(const char* db, const char* destFile, const BSONObj& options) const;

		protected:
		private:
			void prepareOptions(Command* cmd);
			Command* parseCommand(const char* expression);

		private:
			NetworkOutputStream*  _outputStream;
			NetworkInputStream*   _inputStream;
			CommandWriter*        _commandWriter;
			char*          _activeTransactionId;

			char* _host;
			int _port;
			bool _open;
			Logger* _logger;
	};

}

#endif // DJONDB_CONNECTION_H
