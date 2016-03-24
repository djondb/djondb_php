#ifndef DBCONTROLLER_H
#define DBCONTROLLER_H

#include <map>
#include <vector>
#include <string>
#include <cstring>
#include "filterdefs.h"
#include "streammanager.h"
#include "controller.h"

class FileInputOutputStream;
class MemoryStream;
class FileInputStream;
class BSONObj;
class BSONArrayObj;
class Command;
class Logger;
class FilterParser;
class Index;
class IndexAlgorithm;
class DBCursor;

class DBController: public Controller
{
    public:
        DBController();
        virtual ~DBController();

        void initialize();
        void initialize(std::string dataDir);
        void shutdown();

        const BSONObj* insert(const char* db, const char* ns, BSONObj* bson, const BSONObj* options = NULL);
		  bool dropNamespace(const char* db, const char* ns, const BSONObj* options = NULL);
        virtual bool update(const char* db, const char* ns, BSONObj* bson, const BSONObj* options = NULL);
        virtual bool remove(const char* db, const char* ns, const char* documentId, const char* revision, const BSONObj* options = NULL);
        virtual DBCursor* const find(const char* db, const char* ns, const char* select, const char* filter, const BSONObj* options = NULL) throw (ParseException);
		  virtual DBCursor* const fetchCursor(const char* cursorId);

        BSONObj* readBSON(StreamType* stream);
		  std::vector<std::string>* dbs(const BSONObj* options = NULL) const;
		  std::vector<std::string>* namespaces(const char* db, const BSONObj* options = NULL) const;

		  static void fillRequiredFields(BSONObj* bson);

		  DBCursor* cursor(const char* cursorId);
		  virtual void releaseCursor(const char* cursorId);

			/*! \brief This method creates indexes on a namespace. The full description of the index elements is included in the indexDef
			*
			* { "db": "db name",
			    "ns": "namespace",
				"name": "Index name",
				"fields": [
				     { "path": "field path" },
				     { "path": "field path 2" }
				]
			  }
			*/
			void createIndex(const BSONObj& indexDef);
			virtual int backup(const char* db, const char* destFile, const BSONObj* options);

	 protected:

    private:
		  Logger* _logger;
		  bool _initialized;
		  std::string _dataDir;
		  MemoryStream* _internalStream; //<! This stream is used as temporal in memory storage to allow the db to write elements to disk in one call

		  struct cmp_str
		  {
			  bool operator()(char const *a, char const *b) const
			  {
				  return std::strcmp(a, b) < 0;
			  }
		  };

		  std::map<const char*, DBCursor*, cmp_str> _cursors;

	 private:
		  BSONArrayObj* findFullScan(const char* db, const char* ns, const char* select, FilterParser* parser, const BSONObj* options) throw (ParseException);
		  void clearCache();
		  long checkStructure(BSONObj* bson);
		  Index const* findIndex(const char* db, const char* ns, BSONObj* bson);
		  void insertIndex(IndexAlgorithm* impl, BSONObj* bson, long filePos);
		  void insertIndexes(const char* db, const char* ns, BSONObj* bson, long filePos);
		  void updateIndex(const char* db, const char* ns, BSONObj* bson, long filePos);
		  void writeBSON(StreamType* stream, BSONObj* obj);
		  void migrateIndex0_3(const char* db, const char* ns, InputStream* stream, IndexAlgorithm* impl);

		  void registerCursor(DBCursor* cursor);
		  bool fetchInternalCursor(DBCursor* cursor);
		  bool nextPage(DBCursor* cursor);
		  bool nextPageIndex(DBCursor* cursor);
		  bool nextPageFullScan(DBCursor* cursor);
		  DBCursor* initializeCursor(const char* db, const char* ns, const char* select, const char* filter, const BSONObj* options);
		  void initializeIndexCursor(DBCursor* cursor);
		  void initializeFullScanCursor(DBCursor* cursor);

		  bool readNextPage(DBCursor* cursor);

		  void migrate033(const char* db, const char* ns);
		  // Upon initialization this checks that the primary key indexes exist
		  void checkIndexes();
};

#endif // DBCONTROLLER_H
