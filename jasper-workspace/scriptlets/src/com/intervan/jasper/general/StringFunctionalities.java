package com.intervan.jasper.general;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.sql.Clob;
import java.sql.SQLException;

/**
 * This class contains every string transformation required by the reports.
 * 
 * @author lgraziani
 * @version 1.1.0
 */
public final class StringFunctionalities {
	/**
	 * Expects to receive a string with the following structure:
	 * 
	 * <p>
	 * <code>#123#1234#12345#</code>
	 * </p>
	 * 
	 * @param chain
	 * @return String
	 */
	public static String parseIDChainFromParamToCSV(String chain) {
		return chain.substring(1, chain.length() - 1).replace('#', ',');
	}

	public static Boolean empty(String str) {
		return str == null || str.trim().isEmpty();
	}

	public static String parseClob(Clob clob) throws IOException, SQLException {
		ByteArrayOutputStream out = new ByteArrayOutputStream();

		copyStream(clob.getAsciiStream(), out);

		return out.toString();
	}

	private static void copyStream(InputStream in, OutputStream out) throws IOException {
		byte[] buf = new byte[1024];
		int len = 0;

		while ((len = in.read(buf)) != -1) {
			out.write(buf, 0, len);
		}
		in.close();
		out.close();
	}
}
