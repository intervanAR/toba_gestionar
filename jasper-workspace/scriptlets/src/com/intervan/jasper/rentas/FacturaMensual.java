package com.intervan.jasper.rentas;

import java.awt.Image;
import java.io.IOException;
import java.math.BigDecimal;
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

public class FacturaMensual extends BaseScriptlet {
	private final Map<BigDecimal, Map<String, Image>> imagenes = new HashMap<BigDecimal, Map<String, Image>>();
	private Boolean imprimeBanelco;
	private Boolean imprimeNroDebitoTn;

	@Override
	public void beforeReportInit() throws JRScriptletException {
		super.beforeReportInit();

		this.imprimeBanelco = VariosDao.getConfiguracion("IMPRIME_BANELCO", this.connection, "S").equals("S");
		this.imprimeNroDebitoTn = VariosDao.getConfiguracion("IMPRIME_DEBITO", this.connection, "S").equals(
				"S");

		String liquidacionesTemp = (String) this.getParameterValue("p_cadena_liq");
		String liquidaciones[] = liquidacionesTemp.substring(1, liquidacionesTemp.length() - 1).split("#");
		String where = "";
		int dontPutOr = liquidaciones.length - 1;

		for (int i = 0; i < liquidaciones.length; i++) {
			where += "id_liquidacion = " + liquidaciones[i];

			if (i == dontPutOr) {
				continue;
			}

			where += " OR ";
		}

		String query = "SELECT liq_deuda.id_liquidacion liquidacion, imagen1, imagen2, imagen3, imagen4 "
				+ "FROM RE_LIQUIDACIONES_DEUDA liq_deuda, RE_NOTAS nota "
				+ "WHERE liq_deuda.id_nota = nota.id_nota " + "AND (" + where + ")";

		this.connection.query(query, new JasperOperation() {
			@Override
			public ResultSet query(PreparedStatement stmt) throws JRScriptletException, SQLException {
				ResultSet rs = stmt.executeQuery();

				while (rs.next()) {
					try {
						BigDecimal liquidacion = rs.getBigDecimal("liquidacion");
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

						HashMap<String, Image> liqImagenes = new HashMap<String, Image>();

						liqImagenes.put("imagen1", imagen1);
						liqImagenes.put("imagen2", imagen2);
						liqImagenes.put("imagen3", imagen3);
						liqImagenes.put("imagen4", imagen4);

						imagenes.put(liquidacion, liqImagenes);
					} catch (IOException e) {
						throw new JRScriptletException(e);
					}
				}

				return rs;
			}
		});
	}

	public Image image(BigDecimal p_liquidacion, String image) {
		return this.imagenes.get(p_liquidacion).get(image);
	}

	public Boolean imprimeBanelco() {
		return this.imprimeBanelco;
	}

	public Boolean imprimeNroDebitoTn() {
		return this.imprimeNroDebitoTn;
	}
}
