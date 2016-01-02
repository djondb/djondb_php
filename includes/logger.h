#ifndef LOGGER_H_INCLUDED
#define LOGGER_H_INCLUDED

#include <string>
#include "defs.h"
#include "util.h"
#include "dtime.h"
#include <ctime>
#include <map>
#ifndef WINDOWS
#include <time.h>
#else
// Include this to reference the timespec struct
#include <pthread.h>
#endif

#ifdef WINDOWS
	#include <Windows.h>
	#include <winsock.h>
	//struct timeval {
	//		long    tv_sec;         /* seconds */
	//		long    tv_usec;        /* and microseconds */
	//};
#endif

using namespace std;

class Logger {
	private:
		class Config {
			public:
			bool m_debug;
			bool m_info;
			bool m_warn;
			int _detail;
			char* m_clazz;
		};
		const char* _clazz;
		Config* _configSettings;

		int _interval;
		struct timespec _ts1;
		struct timespec _ts2;
		bool _timerRunning;
		static std::map<std::string, Logger*> _loggers;

	private:
		Logger();
		void print(std::string type, std::string message);
		void initialize(const char* clazz);

	public:
		virtual ~Logger();
		bool isDebug();
		bool isInfo();
		bool isWarn();
		void debug(string message, ...);
		void debug(int detail, string message, ...);
		void error(string error, ...);
		void error(exception ex);
		void info(string message, ...);
		void warn(string warn, ...);

		void startTimeRecord();
		void stopTimeRecord();

		DTime recordedTime();

		static Logger* const getLogger(const char* clazz);
		static void deleteLogger();
};

#endif
