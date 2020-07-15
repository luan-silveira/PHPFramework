<?php

namespace Database;

class GeradorSQL
{
    const TIPO_CREATE_TABLE = 0;
    const TIPO_ALTER_TABLE = 1;
    const TIPO_DROP_TABLE = 2;

    const ARR_TIPOS_INTEIROS = ['bigint', 'bit', 'int', 'integer', 'smallint', 'tinyint'];
    const ARR_TIPOS_DECIMAIS = ['decimal','double', 'float'];
    const ARR_TIPOS_DATA_HORA = ['date','datetime', 'time', 'timestamp'];
    const ARR_TIPOS_CARACTERES = ['char','varchar'];
    const ARR_TIPOS_SEM_TAMANHO = ['blob', 'longblob', 'longtext', 'text', 'tinytext'];

    const ARR_FUNCOES = ['add', 'modify', 'rename'];

    private $intTipo;

    private $arrSQL = [];

    private $strNomeTabela;
    private $strComentarioTabela;
    private $strCharSet = 'utf8';

    private $arrPrimaryKey = [];
    private $arrForeignKey = [];

    private $arrCamposAdd = [];
    private $arrCamposModify = [];
    private $arrCamposChange = [];
    private $arrCamposDrop = [];
    private $arrIndicesAdd = [];
    private $arrIndicesDrop = [];

    private $strNovoNomeTabela; 

    private $boolCreateIfNotExists = false;
    private $boolDropIfExists = false;



    /**
     * Cria uma tabela utilizando CREATE TABLE
     *
     * @param string  $strNomeTabela   Nome da tabela
     * @param string  $strComentario   Comentário
     * @param string  $strCharSet      Conjunto de caracteres (charset). Padrão: UTF-8
     * @param boolean $boolIfNotExists Adiciona ou não a cláusula IF NOT EXISTS para criar a tabela apenas se ela não existir
     * 
     * @return GeradorSQL
     */
    public function createTable($strNomeTabela, $strComentario = null,  $strCharSet = null, $boolIfNotExists = false)
    {
        $this->intTipo = self::TIPO_CREATE_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        $this->strComentarioTabela = $strComentario;
        if ($strCharSet) $this->strCharSet = $strCharSet;
        $this->boolCreateIfNotExists = $boolIfNotExists;

        return $this;
    }

     /**
     * Cria uma tabela apenas se não existr, utilizando o comando CREATE TABLE IF NOT EXISTS
     *
     * @param string $strNomeTabela Nome da tabela
     * @param string $strComentario Comentário
     * @param string $strCharSet Conunto de caracteres (charset). Padrão: UTF-8
     * 
     * @return GeradorSQL
     */
    public function createTableIfNotExists($strNomeTabela, $strComentario = null,  $strCharSet = null)
    {
        return $this->createTable($strNomeTabela, $strComentario, $strCharSet, true);
    }

     /**
     * Modifica uma tabela utilizando ALTER TABLE
     *
     * @param string $strNomeTabela Nome da tabela
     * 
     * @return GeradorSQL
     */
    public function alterTable($strNomeTabela)
    {
        $this->intTipo = self::TIPO_ALTER_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        return $this;
    }

    /**
     * Renomeia uma tabela utilizando o comando ALTER TABLE [tabela] RENAME TO [novo nome]
     *
     * @param string $strNomeTabela Nome da tabela
     * @param string $strNovoNome   Novo nome
     * 
     * @return GeradorSQL
     */
    public function renameTable($strNomeTabela, $strNovoNome)
    {
        $this->intTipo = self::TIPO_ALTER_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        $this->strNovoNomeTabela = $strNovoNome;
        return $this;
    }

    /**
     * Exclui uma tabela utilizando DROP TABLE
     *
     * @param string  $strNomeTabela Nome da tabela a ser excluída
     * @param boolean $name          Adiciona ou não a cláusula IF EXISTS para exclir apenas caso a tabela já existe. 
     * 
     * @return GeradorSQL
     */
    public function dropTable($strNomeTabela, $boolIfExists = false)
    {
        $this->intTipo = self::TIPO_DROP_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        $this->boolDropIfExists = $boolIfExists;
        return $this;
    }

    /**
     * Exclui uma tabela apenas se já existe, utilizando DROP TABLE IF EXISTS
     *
     * @param string  $strNomeTabela Nome da tabela a ser excluída
     * 
     * @return GeradorSQL
     */
    public function dropTableIfExists($strNomeTabela)
    {
        return $this->dropTable($strNomeTabela, true);
    }

    /**
     * Adiciona um campo para ser incluído na tabela nos comandos CREATE/ALTER TABLE
     *
     * @param string  $strNome             Nome do campo a ser adicionado
     * @param string  $strComentario       Comentário
     * @param string  $strTipo             Tipo de dados (ex.: int(11), varchar(255), bigint(10) unsigned, etc.)
     * @param boolean $boolNulo            (Opcional) Informa se o campo é nulo ou não. Se não for nulo, o campo será declarado como NOT NULL
     * @param mixed   $default             (Opcional) Valor padrão (DEFAULT)
     * @param boolean $boolAutoIncrement   (Opcional) Informa se o campo é AUTO_INCREMENT
     * 
     * @return GeradorSQL
     */
    public function addCampo($strNome, $strComentario = '', $strTipo, $boolNulo = false, $default = null, $boolAutoIncrement = false)
    {
        $this->arrCamposAdd[] = [
            'nome' => $strNome,
            'tipo' => $strTipo,
            'nulo' => $boolNulo,
            'default' => $default,
            'comentario' => $strComentario,
            'ai' => $boolAutoIncrement,
        ];
        return $this;
    }

    // public function addInt($strNome, $strComentario = '', $intTamanho = 11, $boolNulo = false, $default = null, $boolUnsigned = false, $boolAutoIncrement = false)
    // public function addDecimal($strNome, $strComentario = '', $intTamanho = 11, $intPrecisao = 0, $boolNulo = false, $default = null, $boolUnsigned = false, boolAutoIncrement = false)
    // public function addVarchar($strNome, $strComentario = '', $intTamanho = 11, $boolNulo = false, $default = null)
    // public function addDate($strNome, $strComentario = '', $boolNulo = false, $default = null, )
    // public function addTime($strNome, $strComentario = '', $boolNulo = false, $default = null, )
    // public function addTimestamp($strNome, $strComentario = '', $boolNulo = false, $default = null, )
    // public function addText($strNome, $strComentario = '', $boolNulo = false)
    // public function addBlob($strNome, $strComentario = '', $boolNulo = false)

    public function __call($name, $arguments)
    {
        if (!preg_match('/^([a-z]+)([A-Z][\w]*)$/', $name, $arrMatches) || !$this->existeTipo($arrMatches[2])) {
            throw new \BadMethodCallException("A função '$name' não existe!");  
        }

        if (!isset($arguments[0])) {
            throw new \BadMethodCallException("A função '$name' possui o primeiro parâmetro obrigatório");
        }
        
        $strFuncao = $arrMatches[1];
        $strTipoDados = strtolower($arrMatches[2]);
        if (!in_array($strFuncao, self::ARR_FUNCOES)) {
            throw new \BadMethodCallException("Função '$strFuncao' inválida!");
        }

        $i = 2;
        if ($strFuncao == 'rename') $i++; 
        
        if (!isset($arguments[1])) $arguments[1] = null;

        if (in_array($strTipoDados, self::ARR_TIPOS_INTEIROS)) {
            // public function addInt($strNome, $strComentario = '', $intTamanho = 11, $boolNulo = false, $default = null, $boolUnsigned = false, $boolAutoIncrement = false)
            $intTamanho = $this->getItemArray($arguments, $i);
            $boolUnsigned = $this->getItemArray($arguments, $i + 3);
            //-- Substitui o parâmetro do tamamnho pelo tipo de dados completo
            array_splice($arguments, $i, 1, $this->getTipoDados($strTipoDados, $intTamanho, null, $boolUnsigned));
        } else{
            $boolAutoIncrement = false;
            if (in_array($strTipoDados, self::ARR_TIPOS_DECIMAIS)) {
                $intTamanho = $this->getItemArray($arguments, $i);
                $intPrecisao = $this->getItemArray($arguments, $i + 1);
                $boolUnsigned = $this->getItemArray($arguments, $i + 4);
                // public function addDecimal($strNome, $strComentario = '', $intTamanho = 11, $intPrecisao = 0, $boolNulo = false, $default = null, $boolUnsigned = false, boolAutoIncrement = false)
                array_splice($arguments, $i, 1, $this->getTipoDados($strTipoDados, $intTamanho, $intPrecisao, $boolUnsigned));
                array_splice($arguments, $i, 7, $boolAutoIncrement);
            } elseif (in_array($strTipoDados, self::ARR_TIPOS_CARACTERES)) { 
                $intTamanho = $this->getItemArray($arguments, $i);
                array_splice($arguments, $i, 1, $this->getTipoDados($strTipoDados, $intTamanho));              
                 // public function addVarchar($strNome, $strComentario = '', $intTamanho = 11, $boolNulo = false, $default = null)
            } elseif (in_array($strTipoDados, array_merge(self::ARR_TIPOS_DATA_HORA, self::ARR_TIPOS_SEM_TAMANHO))) { 
                array_splice($arguments, $i, 0, $this->getTipoDados($strTipoDados));              
                // public function addDate($strNome, $strComentario = '', $boolNulo = false)
            } else {
                throw new \BadMethodCallException("Tipo de dados '$strTipoDados' inválido!");
            }
        }

        return $this->{"{$strFuncao}Campo"}(...$arguments);
    }

    private function insertItemArray($arr, $intPos, $item)
    {
        array_splice($arr, $intPos, 0, $item);
        return $arr;
    }

    private function getItemArray($arr, $intPos, $default = null)
    {
        return isset($arr[$intPos]) ? $arr[$intPos] : $default;
    }

    private function existeTipo($strTipo)
    {
        return in_array(strtolower($strTipo), array_merge(self::ARR_TIPOS_INTEIROS, self::ARR_TIPOS_DECIMAIS, self::ARR_TIPOS_DATA_HORA, 
            self::ARR_TIPOS_CARACTERES, self::ARR_TIPOS_SEM_TAMANHO));
    }

    private function getTipoDados($strTipo, $intTamanho = null, $intPrecisao = null, $boolUnsigned = false)
    {
        if ($intTamanho) $strTipo .= "($intTamanho" . ($intPrecisao ? ",$intPrecisao" : '') . ')';
        if ($boolUnsigned) $strTipo .= ' unsigned';
        return ($strTipo);
    }

    /**
     * Adiciona um campo para modificar no comando ALTER TABLE
     *
     * @param string  $strNome             Nome do campo a ser adicionado
     * @param string  $strComentario       Comentário
     * @param string  $strTipo             Tipo de dados (ex.: int(11), varchar(255), bigint(10) unsigned, etc.)
     * @param boolean $boolNulo            (Opcional) Informa se o campo é nulo ou não. Se não for nulo, o campo será declarado como NOT NULL
     * @param mixed   $default             (Opcional) Valor padrão (DEFAULT)
     * @param boolean $boolAutoIncrement   (Opcional) Informa se o campo é AUTO_INCREMENT
     * 
     * @return GeradorSQL
     */
    public function modifyCampo($strNome, $strComentario = '', $strTipo, $boolNulo = false, $default = null, $boolAutoIncrement = false)
    {
        $this->arrCamposModify[] = [
            'nome' => $strNome,
            'tipo' => $strTipo,
            'nulo' => $boolNulo,
            'default' => $default,
            'comentario' => $strComentario,
            'ai' => $boolAutoIncrement,
        ];
        return $this;
    }

    /**
     * Adiciona um campo para renomear no comando ALTER TABLE
     *
     * @param string  $strNome             Nome do campo a ser adicionado
     * @param string  $strNovoNome         Novo nome
     * @param string  $strComentario       Comentário
     * @param string  $strTipo             Tipo de dados (ex.: int(11), varchar(255), bigint(10) unsigned, etc.)
     * @param boolean $boolNulo            (Opcional) Informa se o campo é nulo ou não. Se não for nulo, o campo será declarado como NOT NULL
     * @param mixed   $default             (Opcional) Valor padrão (DEFAULT)
     * @param boolean $boolAutoIncrement   (Opcional) Informa se o campo é AUTO_INCREMENT
     * 
     * @return GeradorSQL
     */
    public function renameCampo($strNome, $strNovoNome, $strComentario = '', $strTipo, $boolNulo = false, $default = null, $boolAutoIncrement = false)
    {
        $this->arrCamposChange[] = [
            'nome' => $strNome,
            'novo_nome' => $strNovoNome,
            'tipo' => $strTipo,
            'nulo' => $boolNulo,
            'default' => $default,
            'comentario' => $strComentario,
            'ai' => $boolAutoIncrement,
        ];
        return $this;
    }

    /**
     * Adiciona um campo para excluir no comando ALTER TABLE
     *
     * @param string $strCampo
     * 
     * @return GeradorSQL
     */
    public function dropCampo($strCampo)
    {
        $this->arrCamposDrop[] = $strCampo;
        return $this;
    }

    /**
     * Adiciona um campo para ser incluído na tabela como chave primária nos comandos CREATE/ALTER TABLE
     *
     * @param string  $strNome             Nome do campo a ser adicionado
     * @param string  $strComentario       Comentário
     * @param string  $strTipo             Tipo de dados (ex.: int(11), varchar(255), bigint(10) unsigned, etc.)
     * @param boolean $boolAutoIncrement   (Opcional) Informa se o campo é AUTO_INCREMENT
     * 
     * @return GeradorSQL
     */
    public function addCampoPrimaryKey($strNome, $strComentario = '', $strTipo, $boolAutoIncrement = false)
    {
        $this->addCampo($strNome, $strComentario, $strTipo, false, null, $boolAutoIncrement);
        $this->setPrimaryKey($strNome);
        return $this;
    }

    /**
     * Adiciona um campo para ser incluído na tabela como chave estrangeira nos comandos CREATE/ALTER TABLE
     *
     * @param string  $strNome             Nome do campo a ser adicionado
     * @param string  $strComentario       Comentário
     * @param string  $strTipo             Tipo de dados (ex.: int(11), varchar(255), bigint(10) unsigned, etc.)
     * @param string  $strTabelaRef        Tabela de referência (REFERENCES)
     * @param string  $strCampoRef         (Opcional) Campo da tabela de referência. Se não for informado, será o mesmo nome do campo
     * @param boolean $boolNulo            (Opcional) Informa se o campo é nulo ou não. Se não for nulo, o campo será declarado como NOT NULL
     * @param mixed   $default             (Opcional) Valor padrão (DEFAULT)
     * @param boolean $boolAutoIncrement   (Opcional) Informa se o campo é AUTO_INCREMENT
     * 
     * @return GeradorSQL
     */
    public function addCampoForeignKey($strNome, $strComentario = '', $strTipo, $strTabelaRef, $strCampoRef = null, $boolNulo = false, $strNomeConstraint = false) {
        $this->addCampo($strNome, $strComentario, $strTipo, $boolNulo, null, false);
        $this->addForeignKey($strNome, $strTabelaRef, $strCampoRef);
        return $this;
    }

    /**
     * Define os campos da tabela que compõem a chave primária (PRIMARY KEY) nos comandos CREATE/ALTER TABLE
     *
     * @param array ...$arrCampos (Parâmetro variável) Lista de campos de chave primária
     * 
     * @return GeradorSQL
     */
    public function setPrimaryKey(...$arrCampos)
    {
        $this->arrPrimaryKey = array_merge($this->arrPrimaryKey, $arrCampos);
        return $this;
    }

    /**
     * Adiciona uma restrição (CONSTRAINT) de chave estrangeira (FOREIGN KEY) nos comandos CREATE/ALTER TABLE
     *
     * @param string $strCampo          Nome do campo
     * @param string $strTabelaRef      Tabela de referência
     * @param string $strCampoRef       (Opcional) Campo de referência
     * @param string $strNomeConstraint (Opcional) Nome da constraint
     * 
     * @return GeradorSQL
     */
    public function addForeignKey($strCampo, $strTabelaRef, $strCampoRef = null, $strNomeConstraint = null)
    {
        $this->arrForeignKey[] = [
            'campo' => $strCampo,
            'tabela_ref' => $strTabelaRef,
            'campo_ref' => $strCampoRef ?: $strCampo,
            'constraint' => $strNomeConstraint, 
        ];
        return $this;
    }

    /**
     * Adiciona um índice na tabela informada nos comandos CREATE/ALTER TABLE
     *
     * @param string  $strNome      Nome do índice
     * @param boolean $boolUnique   Informa se o índice é único (UNIQUE)
     * @param array   ...$arrCampos (Parâmetro variável) Lista de campos. Deve ser informado ao menos um campo.
     * 
     * @return GeradorSQL
     */
    public function addIndex($strNome, $boolUnique, ...$arrCampos)
    {
        $this->arrIndicesAdd[] = [
            'nome' => $strNome,
            'campos' => $arrCampos,
            'unico' => $boolUnique,
        ];
        return $this;
    }

    /**
     * Adiciona um índice único na tabela informada nos comandos CREATE/ALTER TABLE
     *
     * @param string  $strNome      Nome do índice
     * @param array   ...$arrCampos (Parâmetro variável) Lista de campos. Deve ser informado ao menos um campo.
     * 
     * @return GeradorSQL
     */
    public function addUniqueIndex($strNome, ...$arrCampos)
    {
        return $this->addIndex($strNome, true, ...$arrCampos);
    }

    /**
     * Remove um índice no comando ALTER TABLE
     *
     * @param string $strNome Nome do índice
     * 
     * @return GeradorSQL
     */
    public function dropIndex($strNome)
    {
        $this->arrIndicesDrop[] = $strNome;
        return $this;
    }

    /**
     * Gera o comando CREATE TABLE
     * 
     * @return string
     */
    private function gerarCreateTable()
    {
        if ($this->intTipo != self::TIPO_CREATE_TABLE) return false;
        if (!$this->strNomeTabela || !$this->arrCamposAdd) return false;

        $arrCampos = $this->getDefCamposCreateAlter();
        $arrFK = $this->getDefForeignKeysCreateAlter();
        $arrIndicesAdd = $this->getDefIndicesCreateAlter();

        $strPK = $this->getStrPrimaryKeyCreateAlter();

        $strSQL = "CREATE TABLE " . ($this->boolCreateIfNotExists ? 'IF NOT EXISTS ' : '') . "`{$this->strNomeTabela}` (\n" . implode(",\n", $arrCampos);
        if ($strPK) $strSQL .= ",\n" . $strPK;
        if ($arrIndicesAdd) $strSQL .= ",\n" . implode(",\n", $arrIndicesAdd);
        if ($arrFK) $strSQL .= ",\n" . implode(",\n", $arrFK);
        $strSQL .= "\n) DEFAULT CHARSET={$this->strCharSet}";
        if ($this->strComentarioTabela) $strSQL .= " COMMENT='{$this->strComentarioTabela}'";

        return $strSQL;
    }

    /**
     * Gera o comando ALTER TABLE
     * 
     * @return string
     */
    private function gerarAlterTable()
    {
        if ($this->intTipo != self::TIPO_ALTER_TABLE) return false;
        if (!$this->strNomeTabela ||
                (!$this->arrCamposAdd && !$this->arrCamposChange && 
                 !$this->arrCamposDrop && !$this->arrCamposModify &&
                 !$this->arrPrimaryKey && !$this->arrForeignKey &&
                 !$this->strNovoNomeTabela)  
            ) return false;
        
        $strPK = $this->getStrPrimaryKeyCreateAlter(true);
        $arrCamposAdd = $this->getDefCamposCreateAlter(true);
        $arrCamposChange = $this->getDefCamposChange();
        $arrFK = $this->getDefForeignKeysCreateAlter(true);
        $arrIndicesAdd = $this->getDefIndicesCreateAlter(true);
        $arrModify = $this->getDefCamposModify();
        $strDropCampos = $this->getDefCamposDrop();
        $strDropIndices = $this->getDefIndicesDrop();

        $arrAlter = [];
        if ($arrCamposAdd) $arrAlter[] = "\n" . implode(",\n", $arrCamposAdd);
        if ($arrCamposChange) $arrAlter[] = "\n" . implode(",\n", $arrCamposChange);
        if ($arrIndicesAdd) $arrAlter[] = "\n" . implode(",\n", $arrIndicesAdd);
        if ($strPK) $arrAlter[] = "\n" . $strPK;
        if ($arrFK) $arrAlter[] = "\n" . implode(",\n", $arrFK);
        if ($arrModify) $arrAlter[] = "\n" . implode(",\n", $arrModify);
        if ($strDropCampos) $arrAlter[] = "\n" . implode(",\n", $strDropCampos);
        if ($strDropIndices) $arrAlter[] = "\n" . implode(",\n", $strDropIndices);
        
        $strSQL = "ALTER TABLE `{$this->strNomeTabela}`" . implode(',', $arrAlter);

        if ($this->strNovoNomeTabela) $arrAlter[] = "\n    RENAME TO `{$this->strNovoNomeTabela}`";

        return $strSQL;        
    }

    /**
     * Gera o comando DROP TABLE
     * 
     * @return string
     */
    private function gerarDropTable()
    {
        $strSQL = "DROP TABLE " . ($this->boolDropIfExists ? 'IF EXISTS ' : '') . "`{$this->strNomeTabela}`";
        $this->arrSQL[] = $strSQL;

        return $strSQL;
    }


    /**
     * Gera um comando SQL a partir dos dados previamente populados e armazena na lista de SQL gerados.
     * 
     * @return string
     */
    public function gerarSQL($boolArmazenar = true)
    {
        if ($this->intTipo === null) return false;

        $strSQL = '';
        switch ($this->intTipo) {
            case self::TIPO_CREATE_TABLE:
                $strSQL = $this->gerarCreateTable();
            break;
            case self::TIPO_ALTER_TABLE:
                $strSQL =  $this->gerarAlterTable();
            break;
            case self::TIPO_DROP_TABLE:
                $strSQL =  $this->gerarDropTable();
        }

        if ($boolArmazenar) $this->armazenarSQL($strSQL);

        return $strSQL;
    }

    private function getDefCamposCreateAlter($boolAlter = false)
    {
        return array_map(function($arrCampo) use ($boolAlter) {
            extract($arrCampo);

            $strSQL = '    ' . ($boolAlter ? 'ADD ' : '');
            $strSQL .= "`$nome` {$tipo}";
            if (!$nulo) $strSQL .= ' NOT NULL';
            if ($default !== null) $strSQL .= " DEFAULT '$default'";
            if ($ai) $strSQL .= ' AUTO_INCREMENT';
            if ($comentario) $strSQL .= " COMMENT '$comentario'";

            return $strSQL;
        }, $this->arrCamposAdd);        
    }

    private function getDefForeignKeysCreateAlter($boolAlter = false)
    {
        return array_map(function($arrFK) use ($boolAlter) {
            extract($arrFK);

            $strSQL = '    ' . ($boolAlter ? 'ADD ' : '');
            if ($constraint) $strSQL .= "CONSTRAINT `$constraint` ";
            $strSQL .= "FOREIGN KEY (`{$campo}`) REFERENCES `$tabela_ref` (`$campo_ref`)";

            return $strSQL;
        }, $this->arrForeignKey);
    }

    private function getDefIndicesCreateAlter($boolAlter = false)
    {
        return array_map(function($arrIndices) use ($boolAlter) {
            extract($arrIndices);

            $arrCampos = array_map(function($strCampo){
                return "`$strCampo`";
            }, $campos);

            $strSQL = '    ' . ($boolAlter ? 'ADD ' : '');
            if ($unico) $strSQL .= 'UNIQUE ';
            $strSQL .= "INDEX `$nome` (" . implode(',', $arrCampos) . ")";

            return $strSQL;
        }, $this->arrIndicesAdd);
    }

    private function getStrPrimaryKeyCreateAlter($boolAlter = false)
    {
        if (!$this->arrPrimaryKey) return null;
        $strSQL = '    ' . ($boolAlter ? 'ADD ' : '');
        $arrCampos = array_map(function($strCampo){
            return "`$strCampo`";
        }, $this->arrPrimaryKey);
        $strSQL .= 'PRIMARY KEY (' . $this->separarCamposVirgula($arrCampos) . ')';

        return $strSQL;
    }

    private function getDefCamposChange()
    {
        return array_map(function($arrCampo) {
            extract($arrCampo);

            $strSQL = "    CHANGE `$nome` `$novo_nome` {$tipo}";
            if ($nulo) $strSQL .= ' NOT NULL';
            if ($default !== null) $strSQL .= " DEFAULT '$default'";
            if ($ai) $strSQL .= ' AUTO_INCREMENT';
            if ($comentario) $strSQL .= " COMMENT '$comentario'";

            return $strSQL;
        }, $this->arrCamposChange);        
    }

    private function getDefCamposModify()
    {
        return array_map(function($arrCampo) {
            extract($arrCampo);

            $strSQL = "    MODIFY `$nome` {$tipo}";
            if ($nulo) $strSQL .= ' NOT NULL';
            if ($default !== null) $strSQL .= " DEFAULT '$default'";
            if ($ai) $strSQL .= ' AUTO_INCREMENT';
            if ($comentario) $strSQL .= " COMMENT '$comentario'";

            return $strSQL;
        }, $this->arrCamposModify);        
    }

    private function getDefCamposDrop()
    {
        return array_map(function($strCampo) {
           return "    DROP COLUMN `$strCampo`";
        }, $this->arrCamposDrop);        
    }

    private function getDefIndicesDrop()
    {
        return array_map(function($strCampo) {
           return "    DROP INDEX `$strCampo`";
        }, $this->arrIndicesDrop);     
    }

    private function separarCamposVirgula($arrCampos)
    {
        return implode(',', $arrCampos);
    }

    private function armazenarSQL($strSQL)
    {
        $this->arrSQL[] = $strSQL;
        $this->zerarCampos();
    }

    public function getAllSQL()
    {
        return implode(";\n\n", $this->arrSQL) . ';';
    }

    public function limparSQL()
    {
        $this->arrSQL = [];
    }

    private function zerarCampos()
    {
       $this->intTipo = null;
       $this->strNomeTabela = null;
       $this->strNovoNomeTabela = null; 
       $this->strComentarioTabela = null;
       $this->strCharSet = 'utf8';
       $this->arrPrimaryKey = [];
       $this->arrForeignKey = [];
       $this->arrCamposAdd = [];
       $this->arrCamposModify = [];
       $this->arrCamposChange = [];
       $this->arrCamposDrop = [];
       $this->arrIndicesAdd = [];
       $this->arrIndicesDrop = [];
    }

    /**
     * Define uma instância da classe DB para execução dos comandos gerados;
     *
     * @param DB $db Instância da classe DB representando a conexão com o banco de dados
     */
    public function setConexaoDB($db)
    {
        $this->db = $db;
        $this->db->setLancarExececaoErros(true);
        return $this;
    }

}