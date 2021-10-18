<?php

class dao_fechas
{
    public static function get_fecha_actual($formato = 'YYYY-MM-DD')
    {
        $sql = "SELECT TO_CHAR(TRUNC(SYSDATE),'".$formato."') fecha FROM DUAL";
        $datos = toba::db()->consultar_fila($sql);

        return $datos['fecha'];
    }

    public static function get_dia_actual()
    {
        $sql = "SELECT TO_CHAR(TRUNC(SYSDATE),'DD') dia FROM DUAL";
        $datos = toba::db()->consultar_fila($sql);

        return $datos['dia'];
    }

    public static function get_mes_actual()
    {
        $sql = "SELECT TO_CHAR(TRUNC(SYSDATE),'MM') mes FROM DUAL";
        $datos = toba::db()->consultar_fila($sql);

        return $datos['mes'];
    }

    public static function get_año_actual()
    {
        $sql = "SELECT TO_CHAR(TRUNC(SYSDATE),'YYYY') anio FROM DUAL";
        $datos = toba::db()->consultar_fila($sql);

        return $datos['anio'];
    }

    public static function calcular_edad($fecha_nacimiento)
    {
        list($Y, $m, $d) = explode('-', $fecha_nacimiento);
        $dia = self::get_dia_actual();
        $mes = self::get_mes_actual();
        $ani = self::get_año_actual();

        return  $mes.$dia < $m.$d ? $ani - $Y - 1 : $ani - $Y;
    }

    /**
     * Suma/resta meses a una fecha.
     *
     * @param fecha Fecha a modificar
     *
     * @var DateTime
     *
     * @param cantidad de meses a sumar/restar
     *
     * @var int
     *
     * return DateTime
     */
    public static function sumar_meses($fecha, $cantidad)
    {
        $suma = $cantidad;

        $dt = clone $fecha;
        $oldDay = $dt->format('d');
        if ($cantidad >= 0) {
            $dt->add(new DateInterval('P'.$suma.'M'));
        } else {
            $dt->sub(new DateInterval('P'.abs($cantidad).'M'));
        }
        $newDay = $dt->format('d');

        if ($oldDay != $newDay) {
            // Check if the day is changed, if so we skipped to the next month.
            // Substract days to go back to the last day of previous month.
            $dt->sub(new DateInterval('P'.$newDay.'D'));
        }
        $fch = clone $dt;

        return $fch;
    }

    /**
     * Retorna período de fechas entre $inicio y $fin (incluyendo ambas fechas).
     *
     * @param  string    fecha de inicio del período
     * @param  string    fecha de fin del período
     *
     * @return DateInterval
     */
    public static function get_periodo($inicio, $fin)
    {
        $inicio = new DateTime($inicio);
        $fin = new DateTime($fin);
        $fin->modify('+1 day');
        $periodo = new DatePeriod(
            $inicio,
            new DateInterval('P1D'),
            $fin
        );

        return $periodo;
    }
}
