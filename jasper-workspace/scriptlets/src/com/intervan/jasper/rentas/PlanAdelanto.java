package com.intervan.jasper.rentas;

import java.awt.Image;
import java.io.IOException;
import java.sql.Blob;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import javax.imageio.ImageIO;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;

/**
 * @author lgraziani
 * @version 1.1.0
 */
public class PlanAdelanto extends BaseScriptlet {
	private String observacion;
	private Image banner;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		final String idPlanMasivo = (String) this.getParameterValue("p_id_plan_masivo");
		String query = "SELECT observacion, imagen FROM RE_PLANES_MASIVOS WHERE id_plan_masivo = ?";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				stmt.setString(1, idPlanMasivo);

				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					try {
						Blob blob = rs.getBlob("imagen");

						observacion = rs.getString("observacion");
						banner = (blob == null) ? null : (Image) ImageIO.read(blob.getBinaryStream());
					} catch (IOException e) {
						throw new JRScriptletException(e);
					}
				}
				return rs;
			}
		});
	}

	public String observacion() {
		return this.observacion;
	}

	public Image banner() {
		return this.banner;
	}
}
