package com.intervan.jasper.rentas;

import java.awt.Image;
import java.io.IOException;
import java.sql.Blob;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

import javax.imageio.ImageIO;

import net.sf.jasperreports.engine.JRScriptletException;

import com.intervan.jasper.general.BaseScriptlet;
import com.intervan.jasper.general.JasperOperation;
import com.intervan.jasper.rentas.daos.VariosDao;

/**
 * @author lgraziani
 * @version 1.1.0
 */
public class FacturaMensualConvenio extends BaseScriptlet {
	private Map<String, Image> imagenes = new HashMap<String, Image>();
	private Boolean imprimeBanelco;
	private Boolean imprimeNroDebitoTn;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		this.imprimeBanelco = VariosDao.getConfiguracion("IMPRIME_BANELCO", this.connection, "S").equals("S");
		this.imprimeNroDebitoTn = VariosDao.getConfiguracion("IMPRIME_DEBITO", this.connection, "S").equals(
				"S");

		String query = "SELECT imagen1, imagen2, imagen3, imagen4 "
				+ "FROM RE_NOTAS "
				+ "WHERE id_nota = (SELECT valor FROM re_configuraciones WHERE campo = 'NOTA_RUTA_CONVENIOS')";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					try {
						Blob blob1 = rs.getBlob("imagen1");
						Blob blob2 = rs.getBlob("imagen2");
						Blob blob3 = rs.getBlob("imagen3");
						Blob blob4 = rs.getBlob("imagen4");

						Image imagen1 = (blob1 == null) ? null
								: (Image) ImageIO.read(blob1.getBinaryStream());
						Image imagen2 = (blob2 == null) ? null
								: (Image) ImageIO.read(blob2.getBinaryStream());
						Image imagen3 = (blob3 == null) ? null
								: (Image) ImageIO.read(blob3.getBinaryStream());
						Image imagen4 = (blob4 == null) ? null
								: (Image) ImageIO.read(blob4.getBinaryStream());

						imagenes.put("imagen1", imagen1);
						imagenes.put("imagen2", imagen2);
						imagenes.put("imagen3", imagen3);
						imagenes.put("imagen4", imagen4);
					} catch (IOException e) {
						throw new JRScriptletException(e);
					}
				}

				return rs;
			}
		});
	}

	public Image image(String image) {
		return this.imagenes.get(image);
	}

	public Boolean imprimeBanelco() {
		return this.imprimeBanelco;
	}

	public Boolean imprimeNroDebitoTn() {
		return this.imprimeNroDebitoTn;
	}
}
