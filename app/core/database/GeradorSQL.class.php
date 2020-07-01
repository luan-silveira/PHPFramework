<?php

class GeradorSQL
{
    const TIPO_CREATE_TABLE = 0;
    const TIPO_ALTER_TABLE = 1;
    const TIPO_DROP_TABLE = 2;
    const TIPO_INSERT = 3;
    const TIPO_UPDATE = 4;
    const TIPO_DELETE = 5;

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

    private $strNovoNomeTabela = false; 

    public function createTable($strNomeTabela, $strComentario = null,  $strCharSet = 'utf8')
    {
        $this->intTipo = self::TIPO_CREATE_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        $this->strComentarioTabela = $strComentario;
        $this->strCharSet = $strCharSet;
        return $this;
    }

    public function alterTable($strNomeTabela)
    {
        $this->intTipo = self::TIPO_ALTER_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        return $this;
    }

    public function renameTable($strNomeTabela, $strNovoNome)
    {
        $this->intTipo = self::TIPO_ALTER_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        $this->strNovoNomeTabela = $strNovoNome;
        return $this;
    }

    public function dropTable($strNomeTabela)
    {
        $this->intTipo = self::TIPO_DROP_TABLE;
        $this->strNomeTabela = $strNomeTabela;
        return $this;
    }

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

    public function dropCampo($strCampo)
    {
        $this->arrCamposDrop[] = $strCampo;
        return $this;
    }

    public function addCampoPrimaryKey($strNome, $strComentario = '', $strTipo, $boolAutoIncrement = false)
    {
        $this->addCampo($strNome, $strComentario, $strTipo, false, null, $boolAutoIncrement);
        $this->setPrimaryKey($strNome);
        return $this;
    }

    public function addCampoForeignKey($strNome, $strComentario = '', $strTipo, $strTabelaRef, $strCampoRef = null, $boolNulo = false, $strNomeConstraint = false) {
        $this->addCampo($strNome, $strComentario, $strTipo, $boolNulo, null, false);
        $this->addForeignKey($strNome, $strTabelaRef, $strCampoRef);
        return $this;
    }

    public function setPrimaryKey(...$arrCampos)
    {
        $this->arrPrimaryKey = array_merge($this->arrPrimaryKey, $arrCampos);
        return $this;
    }

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

    public function addIndex($strNome, $boolUnique, ...$arrCampos)
    {
        $this->arrIndicesAdd[] = [
            'nome' => $strNome,
            'campos' => $arrCampos,
            'unico' => $boolUnique,
        ];
        return $this;
    }

    public function addUniqueIndex($strNome, ...$arrCampos)
    {
        return $this->addIndex($strNome, true, ...$arrCampos);
    }

    public function dropIndex($strNome)
    {
        $this->arrIndicesDrop[] = $strNome;
        return $this;
    }

    private function gerarCreateTable()
    {
        if ($this->intTipo != self::TIPO_CREATE_TABLE) return false;
        if (!$this->strNomeTabela || !$this->arrCamposAdd) return false;

        $arrCampos = $this->getDefCamposCreateAlter();
        $arrFK = $this->getDefForeignKeysCreateAlter();
        $arrIndicesAdd = $this->getDefIndicesCreateAlter();

        $strPK = $this->getStrPrimaryKeyCreateAlter();

        $strSQL = "CREATE TABLE {$this->strNomeTabela} (\n" . implode(",\n", $arrCampos);
        if ($strPK) $strSQL .= ",\n" . $strPK;
        if ($arrIndicesAdd) $strSQL .= ",\n" . implode(",\n", $arrIndicesAdd);
        if ($arrFK) $strSQL .= ",\n" . implode(",\n", $arrIndicesAdd);
        $strSQL .= "\n) DEFAULT CHARSET={$this->strCharSet}";
        if ($this->strComentarioTabela) $strSQL .= " COMMENT='{$this->strComentarioTabela}'";

        $this->armazenarSQL($strSQL);

        return $strSQL;
    }

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
        $arrFK = $this->getDefForeignKeysCreateAlter(true);
        $arrIndicesAdd = $this->getDefIndicesCreateAlter(true);
        $arrModify = $this->getDefCamposModify(true);
        $strDropCampos = $this->getDefCamposDrop(true);
        $strDropIndices = $this->getDefIndicesDrop(true);

        $strSQL = "ALTER TABLE `{$this->strNomeTabela}`";
        if ($arrCamposAdd) $strSQL .= ",\n" . implode(",\n", $arrCamposAdd);
        if ($arrIndicesAdd) $strSQL .= ",\n" . implode(",\n", $arrIndicesAdd);
        if ($strPK) $strSQL .= ",\n" . $strPK;
        if ($arrFK) $strSQL .= ",\n" . implode(",\n", $arrFK);
        if ($arrModify) $strSQL .= ",\n" . implode(",\n", $arrModify);
        if ($strDropCampos) $strSQL .= ",\n" . implode(",\n", $strDropCampos);
        if ($strDropIndices) $strSQL .= ",\n" . implode(",\n", $strDropIndices);

        if ($this->strNovoNomeTabela) $strSQL .= "\n    RENAME TO `{$this->strNovoNomeTabela}`";
        $this->armazenarSQL($strSQL);

        return $strSQL;        
    }

    private function gerarDropTable()
    {
        $strSQL = "DROP TABLE `{$this->strNomeTabela}`";
        $this->arrSQL[] = $strSQL;
        $this->zerarCampos();
        return $strSQL;
    }


    public function gerarSQL()
    {
        if ($this->intTipo === null) return false;

        switch ($this->intTipo) {
            case self::TIPO_CREATE_TABLE:
                return $this->gerarCreateTable();
            case self::TIPO_ALTER_TABLE:
                return $this->gerarAlterTable();
            case self::TIPO_DROP_TABLE:
                return $this->gerarDropTable();
        }
    }


    private function getDefCamposCreateAlter($boolAlter = false)
    {
        return array_map(function($arrCampo) use ($boolAlter) {
            extract($arrCampo);

            $strSQL = '    ' . ($boolAlter ? 'ADD ' : '');
            $strSQL .= "`$nome` {$tipo}";
            if ($nulo) $strSQL .= ' NOT NULL';
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
           return "    DROP COLUMN `$nome`";
        }, $this->arrCamposDrop);        
    }

    private function getDefIndicesDrop()
    {
        return array_map(function($strCampo) {
           return "    DROP INDEX `$nome`";
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

}