<?php


class CardType{
    public function __construct(public $name,public $id){}
}
class Person{
    public function __construct(public $name,public array $card_list,public $is_current_user=false){
    }
}

class Card{
    public function __construct(public CardType $cardType,public int $index,public int $priority=0){}
}

class ThrownCard{
    public function __construct(public Card $card,public Person $user){

    }
}
class Deck{
    public array $cardTypesNames = ["spade","heart","clubs","diamond"];
    public array $userNames = ["ramey", "gothe", "krishne", "deepak"];
    public array $cards=[];
    public $current_user_index=3;

    private int $turn = 0;
    public array $users = [];
    public array $cardTypes = [];
    public array $thrown_cards = [];
    public array $leaderboard = [];
    public int $current_round=0;
    public ?Card $current_card;

    public function __construct(){
        $this->loadCardsInDeck();
        $this->loadUser();
        $this->shuffleCards();
    }
    public function loadUser(){
        foreach($this->userNames as $key=>$user){
            array_push($this->users, new Person($user, [], ($key == $this->current_user_index) ? 1:0));
        }
    }
    public function loadCardsInDeck(){
        if(count($this->cards)<52){
            $this->cards = [];
            $this->createCardType();
        }
        foreach($this->cardTypes as $card_Type){
            for($i=1;$i<14;$i++){
                array_push($this->cards, new Card($card_Type,$i,$card_Type->name=='spade' ? 1:0));
            }
        }
    }

    public function createCardType(){
        foreach($this->cardTypesNames as $key=>$type){
            array_push($this->cardTypes, new CardType($type,$key+1));
        }
    }

    public function shuffleCards(){
        shuffle($this->cards);
        shuffle($this->cards);
        foreach($this->cards as $key=>$card){
            $index = $key % 4;
            array_push($this->users[$index]->card_list, $card);
        }
    }

    public function playGame(int $card_index=0){
        $this->thrown_cards[$this->current_round] = [];
        try{
            foreach($this->users as  &$user){
                if($user->is_current_user){
                    $this->throwCardAsUser($user);
                }else{
                     $this->throwCardAsComputer($user);
                }
            }
            $this->checkRoundWinner();
            $this->current_round++;
            $this->current_card = null;
        }catch(Exception $e){
            echo $e->getMessage();
            die();
        }
    }

    public function throwCardAsComputer(&$user){
        $throwable_cards = $this->findThrowableCards($user);
        $array_key = array_keys($throwable_cards)[0];
        $throwing_card = $throwable_cards[$array_key];
        $user->card_list = array_filter($user->card_list, function ($card) use ($throwing_card) { 
            if($card->cardType->id==$throwing_card->cardType->id){
                return !($card->index == $throwing_card->index);
            }
            return true;
        });
        $this->current_card = $throwing_card;
        array_push($this->thrown_cards[$this->current_round],new ThrownCard($throwing_card,$user));
    }

    public function throwCardAsUser(&$user){
        $throwable_cards = $this->findThrowableCards($user);
        $throwing_card=$this->askUserToChooseCard($throwable_cards);
        $user->card_list = array_filter($user->card_list, function ($card) use ($throwing_card) { 
            if($card->cardType->id==$throwing_card->cardType->id){
                return !($card->index == $throwing_card->index);
            }
            return true;
        });
        $this->current_card = $throwing_card;
        array_push($this->thrown_cards[$this->current_round],new ThrownCard($throwing_card,$user));
    }

    public function askUserToChooseCard(&$throwable_cards){
        foreach($this->thrown_cards[$this->current_round] as $thrown_card){
            echo $thrown_card->user->name . "=>" . $thrown_card->card->cardType->name . "=>" . $thrown_card->card->index . PHP_EOL;
        }
        foreach($throwable_cards as $key=>$card){
            echo $card->cardType->name . "==" . $card->index."===".$key.PHP_EOL;
        }
        $array_keys = array_keys($throwable_cards);
        $chooseIndex=(int)fgets(STDIN);
        $thowing_card = $throwable_cards[$array_keys[$chooseIndex]];
        return $thowing_card;
    }
    public function findThrowableCards($user){
        if(isset($this->current_card)){
            $card_in_hand_of_current_type = array_filter($user->card_list,function($card){
                if($this->current_card->cardType->id==$card->cardType->id){
                    return true;
                }
                return false;
            });
            if(count($card_in_hand_of_current_type)==0){
                if($this->current_card->cardType->name !="spade"){
                    $check_spade_cards = array_filter($user->card_list, function ($card) {
                        return $card->cardType->name == "spade";
                    });
                    $spadeCount = count($check_spade_cards);
                    return $spadeCount > 0 ? $check_spade_cards : $user->card_list;
                }
                return $user->card_list;
            }
            $greater_index_cards = array_filter($card_in_hand_of_current_type, function ($card) {
                return $card->index > $this->current_card->index;
            });
            return count($greater_index_cards) > 0 ? $greater_index_cards :$card_in_hand_of_current_type;
        }
        else{
            return $user->card_list;
        }
    }

    public function checkRoundWinner(){
        $thrown_cards = $this->thrown_cards[$this->current_round];
        $current_card = null;
        $winner = null;
        foreach($thrown_cards as $card){
            if(isset($current_card)){
                if($current_card->cardType->id==$card->card->cardType->id){
                    if($current_card->index<$card->card->index){
                        $current_card = $card->card;
                        $winner = $card->user;
                    }
                }else{
                    if($card->card->cardType->name=="spade"){
                        $current_card = $card->card;
                        $winner = $card->user;
                    }
                }
            }else{
                $current_card=$card->card;
                $winner = $card->user;
            }
        }
        $this->leaderboard[$this->current_round] = $winner;
    }
}

$deck = new Deck();
while(count($deck->thrown_cards)<13){
    $deck->playGame();
}




