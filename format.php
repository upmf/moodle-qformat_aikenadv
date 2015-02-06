<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Aiken format question importer.
 *
 * @package    qformat
 * @subpackage aikenadv
 * @copyright  2003 Tom Robb <tom@robb.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Aiken format - a simple format for creating multiple choice questions (with
 * only one correct choice, and no feedback).
 *
 * The format looks like this:
 *
 * 1.Name
 * Question text
 * A) Choice #1
 * B) Choice #2
 * C) Choice #3
 * D) Choice #4
 * ANSWER: B
 *
 * That is,
 *  
 *  + then a number of choices, one to a line. Each line must comprise a letter,
 *    then ')' or '.', then a space, then the choice text.
 *  + Then a line of the form 'ANSWER: X' to indicate the correct answer.
 *
 * Be sure to word "All of the above" type choices like "All of these" in
 * case choices are being shuffled.
 *
 * @copyright  2003 Tom Robb <tom@robb.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_aikenadv extends qformat_default {

   public function provide_import() {
    return true;
  }

    public function readquestions($lines) {
        $questions = array();
        $num_ans = -1;  // no answers so far
        $num_choices = -1; // no choices so far
        $endchar = chr(13); 
        foreach ($lines as $line) {
            $stp = strpos($line,$endchar,0);
            $newlines = explode($endchar,$line);
            $linescount = count($newlines);
            for ($i=0; $i < $linescount; $i++) {
                $nowline = trim($newlines[$i]);
                // Go through the array and build an object called $question
                // When done, add $question to $questions
                if (mb_strlen($nowline) < 2) {
                    continue;
                }

                if (preg_match('/^[A-Z][).][ \t]/', $nowline)) {
                    // A choice. Trim off the label and space, then save
                    $question->answer[] = $this->text_field(
                            htmlspecialchars(trim(mb_substr($nowline, 2)), ENT_NOQUOTES));
                    $question->fraction[] = 0;
                    $question->feedback[] = $this->text_field('');
                    $num_choices++;
                } elseif ((preg_match('/^ANSWER/', $nowline))||(preg_match('/^'.get_string('answer','qformat_aikenadv').'/', $nowline))) {
                    // The line that indicates the correct answer. This question is finised.

                    $ans =  strtoupper(trim(preg_replace('/^[^:]*:/','',$nowline)));
                    if ($ans == "FALSE") {
                        $question->qtype = TRUEFALSE;
                        $question->single = 1;
                        $question->answer = 0;
                        $num_ans = 1;
                        $question->fraction = 1;
                        $question->feedbacktrue = $this->text_field('');
                        $question->feedbackfalse = $this->text_field('');
                        $question->correctanswer = 0;
                    } elseif ($ans == "TRUE") {
                        $question->qtype = TRUEFALSE;
                        $question->single = 1;
                        $question->answer = 1;
                        $question->correctanswer = 0;
                        $num_ans = 1;
                        $question->feedbacktrue = $this->text_field('');
                        $question->feedbackfalse = $this->text_field('');
                    }else{
			    $mult_ans = preg_split('/[\s,]+/', $ans,NULL,PREG_SPLIT_NO_EMPTY);
                            $num_ans = count($mult_ans);

                            for ($j=0;$j<$num_ans;$j++) {
                                $rightans = ord($mult_ans[$j]) - ord('A');
                                $question->fraction[$rightans] = 1.0/$num_ans;         
                             }
                             $question->single = $num_ans == 1; 
                             $questions[] = $question;
                             // Clear array for next question set
                             $question = $this->defaultquestion();
                             continue;           
                    }
                } elseif (preg_match('/^\s*[A-Z]*[0-9]+[.]\s*/', $nowline)) {
                        //clear for new question
                    $question = $this->defaultquestion();
                   
                    // get rid of any question numbers
                    $question->name = $this->create_default_question_name($nowline, get_string('questionname', 'question'));
                    $question->questiontext=  htmlspecialchars(preg_replace('/^\s*[A-Z]*[0-9]+[.]\s*/','',$nowline), ENT_NOQUOTES);
                    $question->qtype = 'multichoice';
                    $num_ans = 0;  // at least one question is real
                    $num_choices = -1;  // number of choices - 1
                } elseif (($num_ans == 0)&& ($num_choices < 0)){
                       //Must be part of a question or choice since no leader
                        $question->questiontext .= htmlspecialchars(trim($nowline), ENT_NOQUOTES);
                }elseif ($num_ans > 0) {
                        continue;
                }
            } 
        }
        return $questions;
    }
    
    protected function text_field($text) {
        return array(
            'text' => htmlspecialchars(trim($text), ENT_NOQUOTES),
            'format' => FORMAT_HTML,
            'files' => array(),
        );
    }

    function readquestion($lines) {
        //this is no longer needed but might still be called by default.php
        return;
    }
}

?>
