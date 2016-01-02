#ifndef INDEX_H_INCLUDED
#define INDEX_H_INCLUDED

#include "indexfactory.h"
#include <list>
#include <string>
#include <set>
#include <string>
#include <string.h>
#include "util.h"

class BSONObj;
class BaseExpression;

class Index {
	public:
		Index() {};
		virtual ~Index();

		Index(const Index& orig);
		BSONObj* key;
		djondb::string documentId;
		long posData;
		long indexPos;
};

class IndexAlgorithm {
	public:
		IndexAlgorithm() {
		}

		IndexAlgorithm(const char* indexName, std::set<std::string> keys) {
			_keys = keys;
			_indexName = strcpy(indexName, strlen(indexName));
			_db = NULL;
			_ns = NULL;
			_unique = true;
		}

		virtual ~IndexAlgorithm() {
			if (_db) free(_db);
			_db = NULL;
			if (_ns) free(_ns);
			_ns = NULL;
			if (_indexName) free(_indexName);
			_indexName = NULL;
		};

		virtual void add(const BSONObj& elem, djondb::string documentId, long filePos) = 0;
		virtual bool update(const BSONObj& elem, djondb::string documentId, long filePos) = 0;
		virtual Index const* find(BSONObj* const elem) = 0;
		virtual bool remove(const BSONObj& elem) = 0;
		virtual std::list<Index const*> find(BaseExpression* filterExpr) = 0;

		virtual const std::set<std::string> keys() const {
			return _keys;
		}

		virtual void setKeys(std::set<std::string> keys) {
			_keys.clear();
			_keys.insert(keys.begin(), keys.end());
		}

		const char* indexName() const {
			return _indexName;
		}

		void setIndexName(const char* name) {
			_indexName = strcpy(name, strlen(name));
		}

		void setDb(const char* db) {
			if (_db != NULL) free(_db);
			_db = strcpy(db);
		}

		const char* db() const {
			return _db;
		}

		void setNs(const char* ns) {
			if (_ns != NULL) free(_ns);
			_ns = strcpy(ns);
		}

		const bool unique() const {
			return _unique;
		}

		void setUnique(bool u) {
			_unique = u;
		}

		const char* ns() const {
			return _ns;
		}

		virtual bool deleteIndex() = 0;
		virtual bool isSupportedVersion() = 0; //!< Each implementation may check if the current version of the file requires updates or changes.
		virtual Version version() const = 0;

	protected:
		std::set<std::string> _keys;
		char* _db;
		char* _ns;
		char* _indexName;
		bool _unique;
};

#endif // INDEX_H_INCLUDED
