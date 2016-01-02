#ifndef INDEXFACTORY_H
#define INDEXFACTORY_H

#include <map>
#include <string>
#include <set>
#include <vector>

class IndexAlgorithm;

using namespace std;

class BSONObj;

typedef std::vector<IndexAlgorithm*> listAlgorithmsType;
typedef listAlgorithmsType* listAlgorithmsTypePtr;
typedef map<std::string, listAlgorithmsTypePtr> listByNSType;
typedef listByNSType* listByNSTypePtr;
typedef map<std::string, listByNSTypePtr> listByDbType;

class IndexFactory
{
    public:
        virtual ~IndexFactory();

        static IndexFactory indexFactory;
		IndexAlgorithm* createIndex(const char* db, const char* ns, const char* indexName, const char* key);
		IndexAlgorithm* createIndex(const char* db, const char* ns, const char* indexName, const std::set<std::string>& keys);
        IndexAlgorithm* findIndex(const char* db, const char* ns, const std::set<std::string>& keys);
        IndexAlgorithm* findIndex(const char* db, const char* ns, const std::string& key);

		/*! \brief Adds the Index implementation to the list of available indexes
		*/
		void addIndex(const char* db, const char* ns, IndexAlgorithm* impl);

		/*! \brief loads an index from the file system, and add it to the available indexes
		*/
		IndexAlgorithm* loadIndex(const char* fileName);

		/*! \brief Returns all the Index algorithms associated with the database and namespace
		*/
		listAlgorithmsTypePtr indexes(const char* db, const char* ns);
		  bool containsIndex(const char* db, const char* ns, const std::string& key);
		  bool containsIndex(const char* db, const char* ns, const std::set<std::string>& keys);
        void dropIndex(const char* db, const char* ns, const std::string& key);
		  void dropIndex(const char* db, const char* ns, const std::set<std::string>& keys);
		  /*! \brief Drop all the indexes associated with the namespace
		  *
		  **/
		  void dropIndexes(const char* db, const char* ns);
		  /*! \brief This allows to delete indexes by name
		  *
		  **/
		  void dropIndexByName(const char* db, const char* ns, const char* indexName);
    protected:
    private:
        IndexFactory();

			listAlgorithmsTypePtr findAlgorithms(const char* db, const char* ns);
		  IndexAlgorithm* findIndex(const listAlgorithmsTypePtr& algorithms, const std::set<std::string>& keys);
		  void dropIndex(const listAlgorithmsTypePtr& algorithms, const std::set<std::string>& keys);

    private:

        listByDbType _indexes;
};

#endif // INDEXFACTORY_H
