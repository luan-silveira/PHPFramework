<?php

namespace Database;

/**
 *
 * @author Usuario
 */
trait SqlParams
{
	/**
	 * Substitui todos os caracteres '?' do comando SQL pelos seus respectivos parâmetros.
	 * 
	 * @param string $strSql
	 * @return string
	 */
	public function aplicarParametrosSql($strSql, $arrParams)
	{
		$intIdx = 0;
		while (strpos($strSql, '?')) {
			$valor = $arrParams[$intIdx++];
			$strSql = preg_replace('/\?/', (is_int($valor) ? $valor : "'$valor'"), $strSql, 1);
		}

		return $strSql;
	}
}
