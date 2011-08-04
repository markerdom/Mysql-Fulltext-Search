<?php

class CStemmer {

    private $VOWEL = '/���������/';
    private $PERFECTIVEGROUND = '/((��|����|������|��|����|������)|((?<=[��])(�|���|�����)))$/';
    private $REFLEXIVE = '/(�[��])$/';
    private $ADJECTIVE = '/(��|��|��|��|���|���|��|��|��|��|��|��|��|��|���|���|���|���|��|��|��|��|��|��|��|��)$/';
    private $PARTICIPLE = '/((���|���|���)|((?<=[��])(��|��|��|��|�)))$/';
    private $VERB = '/((���|���|���|����|����|���|���|���|��|��|��|��|��|��|��|���|���|���|��|���|���|��|��|���|���|���|���|��|�)|((?<=[��])(��|��|���|���|��|�|�|��|�|��|��|��|��|��|��|���|���)))$/';
    private $NOUN = '/(�|��|��|��|��|�|����|���|���|��|��|�|���|��|��|��|�|���|��|���|��|��|��|�|�|��|���|��|�|�|��|��|�|��|��|�)$/';
    private $RVRE = '/^(.*?[���������])(.*)$/';
    private $DERIVATIONAL = '/[^���������][���������]+[^���������]+[���������].*(?<=�)���?$/';
 

    private function s(&$s, $re, $to){
		$orig = $s;
		$s = preg_replace($re, $to, $s);
		return $orig !== $s;
    }
 
    private function m($s, $re){
		return preg_match($re,$s);
    }
	
	
	public function compile( $words ){
		$wordReverse = Array();
		$words = trim( $words );
		$wordList = explode( ' ', $words );
		$wordList = array_diff( $wordList ,Array(' ') );
		foreach( $wordList as $w )
			$wordReverse[] = $this->stem_word( preg_replace('/[^\w]/','',$w) );
		return implode(' ',$wordReverse);
	}

    public function stem_word($word){
		
		$word = mb_strtolower($word, mb_detect_encoding($word));
		$word = str_replace('�', '�', $word);
		$stem = $word;
		do {
		if (!preg_match($this->RVRE, $word, $p)) break;
		$start = $p[1];
		$RV = $p[2];
		if (!$RV) break;

		
		if (!$this->s($RV, $this->PERFECTIVEGROUND, '')) {
			$this->s($RV, $this->REFLEXIVE, '');
			if ($this->s($RV, $this->ADJECTIVE, '')) {
			$this->s($RV, $this->PARTICIPLE, '');
			} else {
			if (!$this->s($RV, $this->VERB, ''))
				$this->s($RV, $this->NOUN, '');
			}
		}
		$this->s($RV, '/�$/', '');
		
		if ($this->m($RV, $this->DERIVATIONAL))
			$this->s($RV, '/����?$/', '');
			
		if (!$this->s($RV, '/�$/', '')) {
			$this->s($RV, '/����?/', '');
			$this->s($RV, '/��$/', '�');
		}

		$stem = $start.$RV;
		} while(false);
		
		return $stem;
	
    }

}


?>
