<?php

require __DIR__ . '/vendor/autoload.php';

use SleekDB\Store;
use SleekDB\Query;

$databaseDirectory = __DIR__."/database";

$itemStore = new Store("items", $databaseDirectory);

if (empty($itemStore->findAll())) {
    $itemStore->insertMany( 
        [["name"=> "Bread", "properties"=> ["is_made_of_flour"=>true]], ["name" => "Lasanha", "properties" => ["is_made_of_flour"=>true,"has_sauce"=>true]], ["name"=>"Steak","properties"=>["is_meat"=>true]]]
    );
}


Class itemGame {

    /**
     * Current item guess 
     */
    protected array $guess;

    /**
     * The final answer when the user wins 
     */
    protected string $answer;
    
    /**
     * Asked Question List  
     */
    protected array $asklist;

    /**
     * Known item List  
     */
    protected array $itemlist;

    /**
     * Initializes the game and randomizes it  
     */
    public function __construct($opts = null){

        if ($opts == null) {
            $opts = new stdClass();
        }

        global $itemStore;
        $this->itemlist = $itemStore->findAll();
        $this->asklist = property_exists($opts, "asklist") ? $opts->asklist : array();
        $this->answer = property_exists($opts, "answer") ? $opts->answer : '';
        $this->guess = property_exists($opts, "guess") ? $opts->guess : $this->itemlist[array_rand($this->itemlist)];
        $_SESSION['game'] = serialize($this);

    }

    /**
     * Decides if should ask or infere  
     */

    private function decide() {
        
        $tests = 0;

        foreach ($this->asklist as $ask => $value) {

            if (  array_key_exists( $ask, $this->guess['properties'] )  && $value == true) {
                $tests++; 
            }

        }
        $_SESSION['game'] = serialize($this);

        if (  $tests == count( $this->guess['properties'] )  ) { 
           return $this->infere();
        } else {
           return $this->ask();
        }


    }

    /**
     * Asks a new question
     */

    private function ask() {
        $difference = array_diff_key($this->guess['properties'],$this->asklist);

        $item = str_replace("_", " ", array_key_first($difference));

        $html = '
        <div class="text-center">
            <p>The item you thought '. $item  .'</p>
            <button class="btn btn-primary" onclick=\'call_game({"action":"property","value":"'.array_key_first($difference).'","is_right":true})\'>Sim</button>
            <button class="btn btn-primary" onclick=\'call_game({"action":"property","value":"'.array_key_first($difference).'","is_right":false})\'>Não</button>
        </div>';

        return $html;
    }


    /**
     * Inferes the answer
     */
    private function infere() {
 
        $item = str_replace("_", " ", $this->guess['name']);

        $html = '
        <div class="text-center">
            <p>The item you thought was '. $item .'</p>
            <button class="btn btn-primary" onclick=\'call_game({"action":"infere","value":"'.$this->guess['name'].'","is_right":true})\'>Sim</button>
            <button class="btn btn-primary" onclick=\'call_game({"action":"infere","value":"'.$this->guess['name'].'","is_right":false})\'>Não</button>
        </div>';

        return $html;
    }

    /**
     * Compares the answer and decides if it keeps up or changes the guess
     */

    private function compare($action) {
 

        if ($action['is_right'] == "true") {
            $this->asklist[$action['value']] = true;
            $this->changeGuess();
        } else {
            $this->asklist[$action['value']] = false;
            
            if (!$this->changeGuess()){

                $_SESSION['game'] = serialize($this);

                return $this->infere();
            }

        }

        $_SESSION['game'] = serialize($this);
        return $this->decide();

        
    }

    /**
     * Tells you that it has won
     */
    private function victory() {
        
        $html = '
        <div class="text-center">
            <p>I won!!</p>
            <button class="btn btn-primary" onclick=\'call_game({"action":"newgame"})\'>Start again</button>
        </div>
        ';        
        return $html;
    }


    /**
     * If the item is wrong, try to find another one
     */

     private function changeGuess() {
        global $itemStore;

        $properties = array();

        foreach($this->asklist as $property => $value) {
            if ($value == true) {
                
                $properties[] = ["properties.".$property,"=",true];

            } else {
                
                $properties[] = ["properties.".$property,"!=",true];
            }

            $properties[] = "AND";
        }
        
        array_pop($properties);

        // die(var_export($properties,true));

        $item = $itemStore->findBy($properties);

        // die (var_export($item,true));

        if (empty($item) || count($item) == 1 && $this->guess['name'] == $item[0]['name']) {
            return false;
        }

        $this->guess = $item[0];

        $_SESSION['game'] = serialize($this);
        return true;
     }

     /**
      * If it has lost, ask for the answer
      */
     private function askAnswer() {

        $html = '
            <div class="text-center">
                <p>Which item did you think of?</p>
                <input id="answer" name="answer" type="text"/>
                <button onclick=\'send_answer()\'>Send</button> 
            </div>
        ';

        return $html;


     }

    /**
     * Gets the answer 
     */
    private function answer($action) {

        if (isset($action["answer"]) && !empty($action["answer"])) {
            $this->answer = str_replace(" ","_",$action["answer"]);

            $_SESSION['game'] = serialize($this);

            return $this->askDetails();
        } else {
            $this->askForInput(["action"=>"newgame"]);
        }
    }

    /** 
     * If it has lost, ask for more properties of the item you were thinking of
     */
    private function askDetails() {

        $html = '
            <div class="text-center">
                <p>'.str_replace("_", " ",$this->answer).' ________ but '.str_replace("_", " ",$this->guess['name']).' isn\'t.</p>
                <input id="property" name="property" type="text"/>
                <button onclick=\'send_property()\'>Send</button> 
            </div>
        ';

        return $html;
     
    }

    private function details($action) {
       
        if (!isset($action["property"]) || empty($action["property"])) {
            $this->askForInput(["action"=>"newgame"]);
        }

        global $itemStore;

        $new_property = str_replace(" ","_",$action["property"]);

        $item = $itemStore->findBy(["name", "=", $this->answer ]);
        $guess = $itemStore->findBy(["name", "=", $this->guess['name'] ]);

        $properties = array_filter($this->asklist);

        $properties[$new_property] = true; 

        if (empty($item)) {
            $itemStore->insert(
                [ "name"=>  $this->answer, "properties"=> $properties]
            );

            $guess[0]["properties"][$new_property] = false; 
            $itemStore->update($guess);

            $_SESSION['game'] = serialize($this);

            return $this->askForInput(["action"=>"newgame"]);

        } else {
            $item[0]["properties"][$new_property] = true; 
            $guess[0]["properties"][$new_property] = false; 

            $itemStore->update($guess);
            $itemStore->update($item);

            $_SESSION['game'] = serialize($this);

            return $this->askForInput(["action"=>"newgame"]);
        }
        
    }

    /**
     * Asks a for input and advances to next step
     */

    public function askForInput(array $action = array("action"=>"")) {

        switch($action['action']) {

            
            case "startguess":

                return $this->decide();
                break;
                

            case "infere":

                return $action["is_right"] == 'true' ? $this->victory() : $this->askAnswer();
                break;

            case "property":

                return $this->compare($action);
                break;

            case "answer":
            
                return $this->answer($action);
                break;

            case "details":
            
                return $this->details($action);
                break;

            case "newgame":

                return $this->newGame();
                break;

            default: 

                $html = '
                <div class="text-center">
                    <p>Think of a common item</p>
                    <button class="btn btn-primary" onclick=\'call_game({"action":"startguess"})\'>Ok</button>
                </div>';

                return $html;
    
        }

        
    }

    public function newGame() {
        
        $this->asklist =  array();
        $this->answer = '';
        $this->guess = $this->itemlist[array_rand($this->itemlist)];
        $_SESSION['game'] = serialize($this);

        $html = '
        <div class="text-center">
            <p>Think of a common item</p>
            <button class="btn btn-primary" onclick=\'call_game({"action":"startguess"})\'>Ok</button>
        </div>';

        return $html;
    }

}