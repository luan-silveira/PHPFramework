<?php

/**
 *
 * @author Usuario
 */
trait MetodoGetSet
{
	private function manipularMetodosGetSetDados(&$arrDados, $strNome, $valor = null)
	{
		$strTipo = substr($strNome, 0, 3);
		if (in_array($strTipo, ['get', 'set'])){
			$strNome = lcfirst(substr($strNome, 3));
			if ($strTipo === 'get') {
				return $arrDados[$strNome] ?? null;
			} elseif ($strTipo === 'set'){
				if(isset($arrDados[$strNome])) $arrDados[$strNome] = $valor;
				return true;
			}
		}
		
		return false;
	}
}
