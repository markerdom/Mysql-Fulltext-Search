<?php

class CStemmer {

    private $VOWEL = '/аеиоуыэюя/';
    private $PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
    private $REFLEXIVE = '/(с[яь])$/';
    private $ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/';
    private $PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
    private $VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
    private $NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/';
    private $RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
    private $DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';
 

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
		$word = str_replace('ё', 'е', $word);
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
		$this->s($RV, '/и$/', '');
		
		if ($this->m($RV, $this->DERIVATIONAL))
			$this->s($RV, '/ость?$/', '');
			
		if (!$this->s($RV, '/ь$/', '')) {
			$this->s($RV, '/ейше?/', '');
			$this->s($RV, '/нн$/', 'н');
		}

		$stem = $start.$RV;
		} while(false);
		
		return $stem;
	
    }

}


?>
