<?php


class ESearch {
	/**
	 * Минимальное кол символов и букв в запросе
	 * @var integer $minWordLength
	 */
	public $minWordLength = 2;
	
	/**
	 * Инициализируем соединение с сервером
	 * @var resource $connection
	 */
	public $connection;
	/**
	 * Init tools and helper variable
	 * @var string $query
	 */
	public $query;
	public $words = Array();
	// Init main string output, default: false
	public $mysqlquery = false;
	
	// Output container
	public $output;
	public $result = false;
	
	private $columns = Array();
	
	private $typeIsset = Array( 'assoc', 'array', 'object' );
	private $typeSet = 'assoc';
	
	public $stemmerClass = false;
	public $stemmerFunction = 'stem_word';
	
	public $selectFields = '*';
	
	public $importantFields = false;
	
	private $table = false;
	
	public $limit = '10';
	
	public function __construct( $connection ){
		
		if( $connection )
			$this->connection = $connection;
		else
			throw new Exception("Неверное подключение к базе данных. Инициализируйте его правильно.");
		
	}
	
	public function setTable( $table ){
		
		$this->table = $table;
		
		return $this;
	}
	
	public function setLimit( $p1, $p2=false ){
		if( !$p2 ) $this->limit = $p1;
		else $this->limit = $p1.','.$p2;
		return $this;
	}
	
	public function selected( $fields ){
		
		if( gettype( $fields ) == 'array' and count( $fields )> 0 )
			$this->selectFields = '`' . implode( '`,`', $fields ) . '`';
		
		return $this;
	}
	
	public function createSearch( $searchstring ){
		
		$this->query = trim( preg_replace( '/([^a-zа-я0-9\-\_\+\s])/ui', '', $searchstring ) );
		
		$tempWords = explode( ' ', $this->query );
		
		$stemmerFunction = $this->stemmerFunction;
		
		if( count( $tempWords ) ){
			if( $this->stemmerClass )
				foreach( $tempWords as $tempWord ) 
					if( mb_strlen( $tempWord, 'UTF-8' ) >= $this->minWordLength ) 
							$this->words[] = mb_strtolower( $this->stemmerClass->$stemmerFunction( $tempWord ) );
			else 
				foreach( $tempWords as $tempWord ) 
					if( mb_strlen( $tempWord, 'UTF-8' ) >= $this->minWordLength ) 
							$this->words[] = mb_strtolower( $tempWord );
					
		}
		
		$this->words = array_unique($this->words);
		
		return $this;
		
	}
	
	public function setColumns( $columns = Array( ) ){
		
		if( !count( $columns ) )
			throw new Exception("Укажите FULLTEXT TYPE поля.");
		
		$this->columns = $columns;
		
		return $this;
		
	}
	
	public function parseFulltextQuery( $arrayWords = Array()  ){
		$stringQuery = '';
		if( count( $arrayWords ) == 0 ) return false;
		
		foreach( $arrayWords as $word )
			if( mb_strlen( $word , 'UTF-8' ) < 4 ) $stringQuery .= $word . ' ';
			else $stringQuery .= $word . '* ';
		return $stringQuery;
	}
	
	public function exec( ){
		error_reporting( E_ALL );
		$case = Array();
		$orderA = Array();
		if( count( $this->words ) > 0 ) foreach ( $this->words as $wordL){
				$RegExpWords[] = trim( $wordL );
				$RegExpWords[] = mb_convert_case($wordL, MB_CASE_TITLE, "UTF-8");
				if( mb_strlen($wordL , 'UTF-8')==3 ) $RegExpWords[] = mb_strtoupper( $wordL, 'UTF-8' );
			}
		else 
			return null;
		$LikeWords = implode( '|',$RegExpWords );
		$ic = 0;
		if( $this->importantFields and count( $this->importantFields ) > 0 ) 
				foreach( $this->importantFields as $important ){
					$case[$ic] = "CASE when `".$important."` REGEXP '(.*)(" . $LikeWords . ")(.*)' then 1 else 0 END as casefield" . $ic;
					$orderA[$ic] = 'casefield'.$ic.' DESC';
					$ic++;
				}
		
		if( !$this->words = $this->filterWordsByLength( $this->words ) ) return null;
				
		if( !$fulltextQuery = $this->parseFulltextQuery( $this->words ) ) return null;
		
		if( count( $this->columns ) > 0 ){
			$fulltextFields = '`'. implode( '`,`', $this->columns ) . '`';
		}else 
			throw new Exception("Прежде чем начать поиск следует указать поля в полнотектовом индексе.");
		
		if( !$this->table )
			throw new Exception("Прежде чем начать поиск следует указать таблицу.");
		
		$match = 'MATCH ('.$fulltextFields.') AGAINST ('. "'". mysql_real_escape_string( $fulltextQuery ) ."'" .' IN BOOLEAN MODE)';
		
		$this->mysqlquery = ( 
				'SELECT ' . $this->selectFields . ( (count( $case )>0)? ','.implode(',', $case ) : '' ) . "\n".
				', ' . $match . ' as `relevance`' ."\n".
				' FROM `' . $this->table . '`' ."\n".
				' WHERE ' . $match ."\n".
				' ORDER BY ' . ( (count( $orderA )>0)? implode(',',$orderA) . ',' : '' ) ."\n".
				'`relevance` DESC' ."\n".
				' LIMIT ' . $this->limit
		);
		
		$this->result = mysql_query( $this->mysqlquery, $this->connection );
		
		if ( mysql_errno( $this->connection ) )
			throw new Exception("Неудалось выполнить запрос. Ошибка MySQL: ". mysql_error( $this->connection ));
		
		$query_function = 'mysql_fetch_' . $this->typeSet;
		
		while( $row = $query_function( $this->result ) ) $this->output[] = $row;
		
		return $this->output;
	}
	
	public function setImportantFields( $arrayFields ){
		
		if( gettype( $arrayFields ) != 'array' ) 
			throw new Exception("Следует передавать важные поля как массив.");
		
		$this->importantFields = $arrayFields;
		
		return $this;
		
	}
	
	public function dataType( $type = 'assoc' ){
		
		if( in_array( $type, $this->typeIsset ) ) $this->typeSet = $type;
		else 
			throw new Exception("Такого типа данных `{$type}` нет.");
		
		return $this;
		
	}
	
	public function filterWordsByLength( $words ){
		if( gettype( $words ) != 'array' ) return false;
		$nwords = Array();
		foreach( $words as $w ) if( mb_strlen( $w, 'UTF-8' ) >= $this->minWordLength ) $nwords[] = $w;
		if( count( $nwords ) == 0 ) return false;
		return $nwords;
	}
	
	public function mindWordsReverse(  ){
		
	}
	
	public function clear(){
		// Deafult variables set
		$this->minWordLength = 3;
		$this->connection = null;
		$this->query;
		$this->words = Array();
		$this->mysqlquery = false;
		$this->output = null;
		$this->result = false;
		$this->columns = Array();
		$this->typeSet = 'assoc';
		$this->stemmerClass = false;
		$this->stemmerFunction = 'stem_word';
		$this->table = false;
		return false;
	}
	
	
	
}

?>
