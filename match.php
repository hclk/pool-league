<?php
namespace Rating;
$rows = array_map('str_getcsv', file('data.csv'));
$headtoheads = array_map('str_getcsv', file('records.csv'));

$player = $record = array();

$n = 0;

foreach($rows as $row){
	if(++$n!==1){
		$player[$row[0]]['name']	= $row[0];
		$player[$row[0]]['elo'] 	= $row[1];
		$player[$row[0]]['wins'] 	= $row[2];
		$player[$row[0]]['losses'] 	= $row[3];
		$player[$row[0]]['winrate'] = $row[4];
		$player[$row[0]]['nemesis'] = $row[5];
	}
} $n=0;

foreach($headtoheads as $headtohead){
	if(++$n!==1){
		$p=0;
		while(++$p<count($headtoheads[0])){
			$record[$headtohead[0]][$headtoheads[0][$p]] = $headtohead[$p];
		}		
	}
}

if(isset($argv[1])&&$argv[1]=="table"){
	poolTable($player);
	die();
}

if(isset($argv[1])&&$argv[1]=="h2h"){
	if(isset($argv[3])){
		if(!isset($record[$argv[2]][$argv[3]])){
			print("No record");
			die();
		} else {
			print($argv[2] . " " . $record[$argv[2]][$argv[3]] . " " . $argv[3]);
			die();
		}
	} else {
		//print_r($record[$argv[2]]);
		foreach($record[$argv[2]] as $playa => $h2hscore){
			if($record[$argv[2]][$playa] !== "0:0" && strlen($playa) > 1){
				print($argv[2] . " " . $h2hscore . " " . $playa . PHP_EOL);
			}			
		}
		die();
		
	}
}

if(isset($argv[1])&&$argv[1]=="history"&&!empty($argv[1])){
	$nh = 0;
	if(!empty($argv[3])){
		if(empty($record[$argv[2]][$argv[3]])){
			print("No record");
		} else {
			print($argv[2] . " " . $record[$argv[2]][$argv[3]] . " " . $argv[3] . PHP_EOL);
			$handle = fopen("history.csv","r");
		    if($handle){
		    	$history = "Head to head history:" .PHP_EOL;	    	
		    	while (($line = fgets($handle)) !== false){
		    		if(strpos($line, $argv[2]) !== false && strpos($line, $argv[3]) !== false){
		    			$history .= $line;
		    			++$nh;
		    		}
				}
			}
		}
	} else {
		$handle = fopen("history.csv","r");
	    if($handle){
	    	$history = "All history:" .PHP_EOL;	    	
	    	while (($line = fgets($handle)) !== false){
	    		if(strpos($line, $argv[2]) !== false){
	    			$history .= $line;
	    			++$nh;
	    		}
			}
		}
	}		
	if($nh>0){
		print($history);
	} else {
		print("No history yet");
	}	
	die();
}

if(isset($argv[1])&&$argv[1]=="undo"){
	if(file_exists('data-1.csv')){
		if(!copy('data-1.csv', 'data.csv')){
			print("Failed to copy data-1 to data");
		}
	} else {
		print("No backup available");
		die();
	}
	if(file_exists('data-2.csv')){
		if(!copy('data-2.csv', 'data-1.csv')){
			print("Failed to copy data-2 to data-1");
		}
	}
	if(file_exists('data-3.csv')){
		if(!copy('data-3.csv', 'data-2.csv')){
			print("Failed to copy data-3 to data-2");
		}
		unlink('data-3.csv');
	}
	$rows = array_map('str_getcsv', file('data.csv'));
	$player = array();
	$n = 0;
	foreach($rows as $row){
		if(++$n!==1){
			$player[$row[0]]['name']	= $row[0];
			$player[$row[0]]['elo'] 	= $row[1];
			$player[$row[0]]['wins'] 	= $row[2];
			$player[$row[0]]['losses'] 	= $row[3];
			$player[$row[0]]['winrate'] = $row[4];
			$player[$row[0]]['nemesis'] = $row[5];
		}
	} $n=0;
	poolTable($player);
	die();
}

if(isset($argv[1])&&isset($argv[2])){
	
	if(file_exists('data-2.csv')){
		copy('data-2.csv', 'data-3.csv');
	}
	if(file_exists('data-1.csv')){
		copy('data-1.csv', 'data-2.csv');
	}
	copy('data.csv', 'data-1.csv');

	if(!isset($player[$argv[1]])){
		//createPlayer($argv[1], $player);
		$player[$argv[1]]['name']	= $argv[1];
		$player[$argv[1]]['elo']	= 1350;
		$player[$argv[1]]['wins']	= 0;
		$player[$argv[1]]['losses']	= 0;
		$player[$argv[1]]['winrate'] = "-";
		$player[$argv[1]]['nemesis'] = "-";
	}
	if(!isset($player[$argv[2]])){
		//createPlayer($argv[2], $player);
		$player[$argv[2]]['name']	= $argv[2];
		$player[$argv[2]]['elo']	= 1350;
		$player[$argv[2]]['wins']	= 0;
		$player[$argv[2]]['losses']	= 0;
		$player[$argv[2]]['winrate'] = "-";
		$player[$argv[2]]['nemesis'] = "-";
	}

	$rating = new Rating($player[$argv[1]]['elo'], $player[$argv[2]]['elo'], 1, 0);

	$player[$argv[1]]['wins']++;
	$player[$argv[2]]['losses']++;
	@$player[$argv[1]]['winrate'] = ($player[$argv[1]]['wins']/($player[$argv[1]]['losses'] + $player[$argv[1]]['wins']));
	@$player[$argv[2]]['winrate'] = ($player[$argv[2]]['wins']/($player[$argv[2]]['losses'] + $player[$argv[2]]['wins']));

	if(!isset($record[$argv[1]][$argv[2]])){
		$record[$argv[1]][$argv[2]] = "1:0";
		$record[$argv[2]][$argv[1]] = "0:1";
	} else {
		$thisRecord = explode(":", $record[$argv[1]][$argv[2]]);
		$winnerWins = $thisRecord[0] + 1;
		$loserWins = $thisRecord[1];
		$record[$argv[1]][$argv[2]] = $winnerWins . ":" . $loserWins; 
		$record[$argv[2]][$argv[1]] = $loserWins . ":" . $winnerWins; 
	}

	$results = $rating->getNewRatings();

	if(!empty($argv[3])&&is_numeric($argv[3])&&$argv[3]<8&&$argv[3]>=0){
		$x = ($player[$argv[1]]['losses'] + $player[$argv[1]]['wins']);
		$results['a'] = $player[$argv[1]]['elo'] + ((($results['a'] - $player[$argv[1]]['elo']) * (1 + (($argv[3] / 7)**1.5))) * ((($x + 0.5)/($x**(($x +6)/6))) + 1));
		$x = ($player[$argv[2]]['losses'] + $player[$argv[2]]['wins']);
		$results['b'] = $player[$argv[2]]['elo'] + ((($results['b'] - $player[$argv[2]]['elo']) * (1 + (($argv[3] / 7)**1.5))) * ((($x + 0.5)/($x**(($x +6)/6))) + 1));
	} else {
		$x = ($player[$argv[1]]['losses'] + $player[$argv[1]]['wins']);
		$results['a'] = $player[$argv[1]]['elo'] + ((($results['a'] - $player[$argv[1]]['elo']) * ((($x + 0.5)/($x**(($x +6)/6))) + 1)));
		$x = ($player[$argv[2]]['losses'] + $player[$argv[2]]['wins']);
		$results['b'] = $player[$argv[2]]['elo'] + ((($results['b'] - $player[$argv[2]]['elo']) * ((($x + 0.5)/($x**(($x +6)/6))) + 1)));
	}

	print("------------------------------------------------------" . PHP_EOL);
	print("New rating for " . $argv[1] . ": " . round($results['a']) . " (" . sprintf("%+d", round($results['a'] - $player[$argv[1]]['elo'])) . ")" . PHP_EOL);
	print("New rating for " . $argv[2] . ": " . round($results['b']) . " (" . sprintf("%+d", round($results['b'] - $player[$argv[2]]['elo'])) . ")" . PHP_EOL);
	print("Head to head: " . $argv[1] . " " . $record[$argv[1]][$argv[2]] . " " . $argv[2] . PHP_EOL);
	print("------------------------------------------------------" . PHP_EOL);

	$player[$argv[1]]['elo'] = $results['a'];
	$player[$argv[2]]['elo'] = $results['b'];

	saveTable($player);
	poolTable($player);

	$output = "Name,";
	foreach($player as $playa){
		$output .= $playa['name'] . ",";
	}
	$output .= PHP_EOL;
	foreach($player as $playa){
		$output .= $playa['name'] . ",";
		foreach($player as $playor){
			if(!isset($record[$playa['name']][$playor['name']])){
				$output .= "0:0,";
			} else {
				$output .= $record[$playa['name']][$playor['name']] .",";
			}
		}
		$output .= PHP_EOL;
	}

	file_put_contents('records.csv', $output);

	if(!empty($argv[3])){
		file_put_contents('history.csv', date('[y-m-d H:i:s]') .": " . $argv[1] . " (" . $results['a'] . ") 1 - 0 " . $argv[2] ." (" . $results['b'] . ") (" . $argv[3] . ")" . PHP_EOL, FILE_APPEND);
	} else {
		file_put_contents('history.csv', date('[y-m-d H:i:s]') .": " . $argv[1] . " (" . $results['a'] . ") 1 - 0 " . $argv[2] ." (" . $results['b'] . ")" . PHP_EOL, FILE_APPEND);
	}

	

} else {
	print("Error getting names or data");
}

function poolTable($player){
	uasort($player, function($a, $b){
		if ($a['elo'] == $b['elo']) {
	        return 0;
	    }
	    return ($a['elo'] < $b['elo']) ? 1 : -1;		
	});
	$r = $ur = 0;
	$unranked = "In placement: ";

	print("#  Name\t\tElo\tWins\tLosses\tWinrate" . PHP_EOL);

	foreach($player as $playa){
		if(isset($playa['name']) && strlen($playa['name'])>1){
			if(($playa['wins'] + $playa['losses']) >2){
				print(++$r . ". " . $playa['name'] . "\t" . round($playa['elo']) . "\t" . $playa['wins'] . "\t" . $playa['losses'] . "\t" . round($playa['winrate'],2)*100 ."%". PHP_EOL);
			} else {
				if(++$ur > 1){
					$unranked .= ", ";
				}
				$unranked .= $playa['name'];
			}
		}
	}
	print("------------------------------------------------------" . PHP_EOL);
	print($unranked);
}



function createPlayer($name, $player){
	$player[$name]['name']	= $name;
	$player[$name]['elo']	= 1350;
	$player[$name]['wins']	= 0;
	$player[$name]['losses']	= 0;
	$player[$name]['winrate'] = "-";
	$player[$name]['nemesis'] = "-";
}

function saveTable($player){
	uasort($player, function($a, $b){
		if ($a['elo'] == $b['elo']) {
	        return 0;
	    }
	    return ($a['elo'] < $b['elo']) ? 1 : -1;		
	});

	$output = "Player,Elo,Wins,Losses,Winrate,Nemesis".PHP_EOL;

	foreach($player as $playa){
		if(isset($playa['name']) && strlen($playa['name'])>1){
			$output .= $playa['name'] . "," . $playa['elo'] . "," . $playa['wins'] . "," . $playa['losses'] . "," . $playa['winrate'] . "," . $playa['nemesis'] . PHP_EOL;
		}
	}
	file_put_contents('data.csv', $output);
}

/**
 * This class calculates ratings based on the Elo system used in chess.
 *
 * @author Michal Chovanec <michalchovaneceu@gmail.com>
 * @copyright Copyright © 2012 - 2014 Michal Chovanec
 * @license Creative Commons Attribution 4.0 International License
 */

class Rating
{

    /**
     * @var int The K Factor used.
     */
    const KFACTOR = 16;

    /**
     * Protected & private variables.
     */
    protected $_ratingA;
    protected $_ratingB;
    
    protected $_scoreA;
    protected $_scoreB;

    protected $_expectedA;
    protected $_expectedB;

    protected $_newRatingA;
    protected $_newRatingB;

    /**
     * Costructor function which does all the maths and stores the results ready
     * for retrieval.
     *
     * @param int Current rating of A
     * @param int Current rating of B
     * @param int Score of A
     * @param int Score of B
     */
    public function  __construct($ratingA,$ratingB,$scoreA,$scoreB)
    {
        $this->_ratingA = $ratingA;
        $this->_ratingB = $ratingB;
        $this->_scoreA = $scoreA;
        $this->_scoreB = $scoreB;

        $expectedScores = $this -> _getExpectedScores($this -> _ratingA,$this -> _ratingB);
        $this->_expectedA = $expectedScores['a'];
        $this->_expectedB = $expectedScores['b'];

        $newRatings = $this ->_getNewRatings($this -> _ratingA, $this -> _ratingB, $this -> _expectedA, $this -> _expectedB, $this -> _scoreA, $this -> _scoreB);
        $this->_newRatingA = $newRatings['a'];
        $this->_newRatingB = $newRatings['b'];
    }

    /**
     * Set new input data.
     *
     * @param int Current rating of A
     * @param int Current rating of B
     * @param int Score of A
     * @param int Score of B
     */
    public function setNewSettings($ratingA,$ratingB,$scoreA,$scoreB)
    {
        $this -> _ratingA = $ratingA;
        $this -> _ratingB = $ratingB;
        $this -> _scoreA = $scoreA;
        $this -> _scoreB = $scoreB;

        $expectedScores = $this -> _getExpectedScores($this -> _ratingA,$this -> _ratingB);
        $this -> _expectedA = $expectedScores['a'];
        $this -> _expectedB = $expectedScores['b'];

        $newRatings = $this ->_getNewRatings($this -> _ratingA, $this -> _ratingB, $this -> _expectedA, $this -> _expectedB, $this -> _scoreA, $this -> _scoreB);
        $this -> _newRatingA = $newRatings['a'];
        $this -> _newRatingB = $newRatings['b'];
    }

    /**
     * Retrieve the calculated data.
     *
     * @return Array An array containing the new ratings for A and B.
     */
    public function getNewRatings()
    {
        return array (
            'a' => $this -> _newRatingA,
            'b' => $this -> _newRatingB
        );
    }

    /**
     * Protected & private functions begin here
     */

    protected function _getExpectedScores($ratingA,$ratingB)
    {
        $expectedScoreA = 1 / ( 1 + ( pow( 10 , ( $ratingB - $ratingA ) / 400 ) ) );
        $expectedScoreB = 1 / ( 1 + ( pow( 10 , ( $ratingA - $ratingB ) / 400 ) ) );

        return array (
            'a' => $expectedScoreA,
            'b' => $expectedScoreB
        );
    }

    protected function _getNewRatings($ratingA,$ratingB,$expectedA,$expectedB,$scoreA,$scoreB)
    {
        $newRatingA = $ratingA + ( self::KFACTOR * ( $scoreA - $expectedA ) );
        $newRatingB = $ratingB + ( self::KFACTOR * ( $scoreB - $expectedB ) );

        return array (
            'a' => $newRatingA,
            'b' => $newRatingB
        );
    }

}

?>
