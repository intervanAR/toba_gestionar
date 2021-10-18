package com.intervan.jasper.general;

import java.util.HashSet;
import java.util.Set;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;

/**
 * Wrapper para log4j
 * 
 * @author lgraziani
 * @version 1.0.0
 */
public class Logger {
	private Log log;
	private Set<String> inlineData = new HashSet<String>();

	public enum Methods {
		info, debug, warn, error, fatal, trace,
	};

	public Logger(Class<?> clazz) {
		this.log = LogFactory.getLog(clazz);
	}

	public void cacheInline(String arg0) {
		this.inlineData.add(arg0);
	}

	public void flushInline(Methods type) {
		String message = "";

		for (String data : this.inlineData) {
			message += " " + data;
		}

		switch (type) {
		case info:
			this.info(message);
			break;
		case debug:
			this.debug(message);
			break;
		case warn:
			this.warn(message);
			break;
		case error:
			this.error(message);
			break;
		case fatal:
			this.fatal(message);
			break;
		case trace:
			this.trace(message);
			break;
		}

		this.inlineData = new HashSet<String>();
	}

	public void debug(String arg0) {
		System.out.println("[DEBUG] " + arg0);
		log.debug(arg0);
	}

	public void debug(String arg0, Throwable arg1) {
		System.out.println("[DEBUG] " + arg0);
		log.debug(arg0, arg1);
	}

	public void error(String arg0) {
		System.out.println("[ERROR] " + arg0);
		log.error(arg0);
	}

	public void error(String arg0, Throwable arg1) {
		System.out.println("[ERROR] " + arg0);
		log.error(arg0, arg1);
	}

	public void fatal(String arg0) {
		System.out.println("[FATAL] " + arg0);
		log.fatal(arg0);
	}

	public void fatal(String arg0, Throwable arg1) {
		System.out.println("[FATAL] " + arg0);
		log.fatal(arg0, arg1);
	}

	public void info(String arg0) {
		System.out.println("[INFO] " + arg0);
		log.info(arg0);
	}

	public void info(String arg0, Throwable arg1) {
		System.out.println("[INFO] " + arg0);
		log.info(arg0, arg1);
	}

	public void trace(String arg0) {
		System.out.println("[TRACE] " + arg0);
		log.trace(arg0);
	}

	public void trace(String arg0, Throwable arg1) {
		System.out.println("[TRACE] " + arg0);
		log.trace(arg0, arg1);
	}

	public void warn(String arg0) {
		System.out.println("[WARN] " + arg0);
		log.warn(arg0);
	}

	public void warn(String arg0, Throwable arg1) {
		System.out.println("[WARN] " + arg0);
		log.warn(arg0, arg1);
	}
}
