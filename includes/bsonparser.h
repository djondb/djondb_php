#ifndef BSONPARSER_H
#define BSONPARSER_H

#include <string>
#include "bsondefs.h"
#include "defs.h"

class BSONObj;
class BSONArrayObj;

class BSONParseException: public std::exception {
	public:
		BSONParseException(int code, const char* error);
		BSONParseException(const BSONParseException& orig);
		virtual const char* what() const throw();
		int errorCode() const;

	private:
		int _errorCode;
		const char* _errorMessage;
};

class BSONParser
{
	public:
		/** Default constructor */
		BSONParser();
		/** Default destructor */
		virtual ~BSONParser();

		/*! \brief parse JSON string representation and returns it as BSON Objects
		 *         if there's an error in the syntax this will throw a BSONParserException
		 *
		 * usage: 
		 *      try {
		 *           BSONObj* obj = BSONParser::parse("{ name: 'John' }");
		 *           // do something
		 *
		 *           delete obj;
		 *      } catch (const BSONParseException& e) {
		 *           // Error in the syntax
		 *      }
		 * */
		static BSONObj* parse(const std::string& sbson);
		static BSONArrayObj* parseArray(const std::string& sbson);

	protected:
	private:
		static BSONObj* parseBSON(const char* c, __int32& pos);
		static BSONArrayObj* parseArray(const char* chrs, __int32& pos);
};

#endif // BSONPARSER_H
