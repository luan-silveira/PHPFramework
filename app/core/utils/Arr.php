<?php

/**
 * Classe de funções úteis para manipulação de Arrays.
 */
class Arr
{

	/**
	 * Função utilizada para mapear os itens de um array numérico ou associativo para um novo array numérico.
	 * Idêntico à função array_map; porém esta função trabalha também com as chaves do array.
	 *
	 * @param array            $array Array
	 * @param callable|Closure $callback Callback
	 * @param bool             $boolPreservarChaves Informa se irá gerar um array numérico ou se irá gerar uma array com as mesmas chaves do anterior.
	 *
	 * @return boolean
	 */
	public static function map($array, $callback, $boolPreservarChaves = false)
	{
		if (empty($array)) {
			return false;
		}

		$arrReturn = [];
		foreach ($array as $key => $value) {
			if ($boolPreservarChaves) {
				$arrReturn[$key] = $callback($value, $key);
			} else {
				$arrReturn[] = $callback($value, $key);
			}
		}

		return $arrReturn;
	}

	/**
	 * Transforma um array com vários sub-arrays em um único array linear.
	 *
	 * @param  array $array Array
	 * @param  int  $limite Limite de profundidade
	 *
	 * @return array
	 */
	public static function achatar($array, $limite = null)
	{
		$arrRetorno = [];
		if ($limite === null || $limite >= 0) {
			foreach ($array as $key => $value) {
				$arrRetorno = array_merge($arrRetorno, (is_array($value) ? self::achatar($value, $limite !== null ? $limite - 1 : null) : [$value]));
			}
		}

		return $arrRetorno;
	}
	
	/**
	 * Filtra o array para conter SOMENTE as colunas informadas. 
	 * 
	 * @param array $array Array
	 * @param array $arrColunas Lista de colunas a serem incluídas.
	 * 
	 * @return array
	 */
	public static function somente($array, $arrColunas)
	{
		return self::filtrarColunas($array, $arrColunas);
	}
	
	/**
	 * Filtra o array para conter todas as colunas EXCETO as informadas.
	 * 
	 * @param array $array Array
	 * @param array $arrColunas Lista de colunas a serem excluídas.
	 * 
	 * @return array
	 */
	public function exceto($array, $arrColunas)
	{
		return self::filtrarColunas($array, $arrColunas, true);
	}

	
	/**
	 * Filtra um array pelas colunas informadas.
	 * 
	 * @param array $array Array associativo a ser filtrado
	 * @param array $arrColunas Array indexado (lista) de colunas para a filtragem
	 * @param type $boolExcluir Informa se a filtragem irá incluir ou excluir as colunas.
	 *							Se informado True, a função irá retornar um array contendo todas as colunas EXCETO as listadas.
	 *							Caso contrário, a função irá retornar um array contendo APENAS as colunas listadas.
	 * @return array Retorna um novo array com as colunas filtradas.
	 */
	private static function filtrarColunas($array, $arrColunas, $boolExcluir = false)
	{
		return array_filter($array, function($key) use ($arrColunas, $boolExcluir) {
			return ($boolExcluir XOR in_array($key, $arrColunas));
		}, ARRAY_FILTER_USE_KEY);
	}
	
	
	/**
	 * Retorna o item de um array multidimensional utilizando notação de ponto.
	 * Ex.: 'item.subitem' = $arrray['item']['subitem']
	 * 
	 * @param array $array Array
	 * @param string $strItem Item no formato de notação de ponto.
	 * 
	 * @return mixed
	 */
	public static function get($array, $strItem){
		$arrChaves = explode('.', rtrim($strItem, '.'));
		$arrChavesAux = [];
		foreach ($arrChaves as $strChave) {
			$arrChavesAux[] = $strChave;
			if (!isset($array[$strChave])){
				throw new \Exception('O item '. implode('.', $arrChavesAux) . ' não existe no array');
			}
			$array = $array[$strChave];
		}
		
		return $array;
	}

}
