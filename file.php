<?php
// =================== form data
$json = file_get_contents('php://input');
$data = json_decode($json);

$entityName = $data->entitnyName != "" ? $data->entitnyName : "Entity_Name";
$modelType = $data->model;
$modelSize= $data->size;
$dataType = $data->type != "" ? $data->type : "std_logic";



// ==================== set port inputs & outputs
$ports = "";
$input_array= array();
$output_array= array();
$no_of_inputs = (int)explode("×", $modelSize)[0];
$no_of_outputs = (int)explode("×", $modelSize)[1];
if($modelType == "mux" || $modelType == "demux"){   // ####### mux & demux
   for ($i=1; $i <= $no_of_inputs; $i++) {
      $input_array[] = "inp_$i";
   }
   $ports .= ";\n ".implode(",", $input_array).": in $dataType ";
   for ($i=1; $i <= $no_of_outputs; $i++) { 
      $output_array[] = "out_$i";
   }
   $ports .= ";\n ".implode(",", $output_array).": out $dataType ";

   $max_val = $no_of_inputs > $no_of_outputs ? $no_of_inputs : $no_of_outputs;
   $no_of_sel = round(sqrt($max_val));
   $ports .= ";\n sel: in std_logic_vector(".($no_of_sel-1)." downto 0) ";
   $input_array[] = "sel";
}
elseif($modelType == "comparator"){   // ####### decoder
   $ports .= ";\n input_1,input_2: in $dataType";
   $input_array[] = "input_1";
   $input_array[] = "input_2";
   $ports .= ";\n greater,less,equal: out std_logic";
}elseif($modelType == "adder_subtractor"){   // ####### adder_subtractor
   $ports .= ";\n input_1,input_2: in $dataType";
   $ports .= ";\n add_sub: in std_logic";
   $input_array[] = "input_1";
   $input_array[] = "input_2";
   $ports .= ";\n sum: out $dataType";
   $ports .= ";\n cout: out std_logic";
}else{  // ####### decoder & incoder
   $ports .= ";\n input: in std_logic_vector(".($no_of_inputs-1)." downto 0)";
   $input_array[] = "input";
   $ports .= ";\n output: out std_logic_vector(".($no_of_outputs-1)." downto 0)";
}



// ==================== set processes
$process = "";
$variables="";
$library="";
if($modelType == "mux"){ // ####### mux
   $process .= "case sel is \n";
   for ($i=1; $i <= $no_of_inputs; $i++) { 
      $process .= "when \"".str_pad(decimalToBinary($i-1), strlen(decimalToBinary($no_of_inputs-1)), '0', STR_PAD_LEFT)."\" => out_1 <= inp_$i; \n";
   }
   $process .= "when others => null; \nend case;";
}elseif($modelType == "demux"){  // ####### demux
   $process .= "case sel is\n";
   for ($i=1; $i <= $no_of_outputs; $i++) { 
      $process .= "when \"".str_pad(decimalToBinary($i-1), strlen(decimalToBinary($no_of_outputs-1)), '0', STR_PAD_LEFT)."\" => out_$i <= inp_1; \n";
   }
   $process .= "when others => null; \nend case;";
}elseif($modelType == "decoder"){  // ####### decoder
   $process .= "case input is\n";
   for ($i=0; $i < $no_of_outputs; $i++) { 
      $process .= "when \"".str_pad(decimalToBinary($i), strlen(decimalToBinary($no_of_outputs-1)), '0', STR_PAD_LEFT)."\" => output <= \"".strrev(substr_replace(str_repeat("0",$no_of_outputs), "1", $i, 1))."\";\n";
   }
   $process .= "when others => null; \nend case;"; 
}elseif($modelType == "encoder"){  // ####### encoder
   $process .= "case input is \n";
   for ($i=0; $i < $no_of_inputs; $i++) { 
      $process .= "when \"".strrev(substr_replace(str_repeat("0",$no_of_inputs), "1", $i, 1))."\" => output <= \"".str_pad(decimalToBinary($i), strlen(decimalToBinary($no_of_inputs-1)), '0', STR_PAD_LEFT)."\"; \n";
   }
   $process .= "when others => null; \nend case;"; 
}elseif($modelType == "comparator"){  // ####### comparator
   $process .= "\nif (input_1 > input_2) then \n less <= '0'; \n equal <= '0'; \n greater <= '1'; \n";
   $process .= "elsif (input_1 < input_2) then \n less <= '1'; \n equal <= '0'; \n greater <= '0'; \n";
   $process .= "elsif (input_1 = input_2) then \n less <= '0'; \n equal <= '1'; \n greater <= '0'; \n";
   $process .= "else then \n less <= '0'; \n equal <= '0'; \n greater <= '0'; \nend if;";
}elseif($modelType == "adder_subtractor"){// ####### adder_subtractor
   switch ($dataType) {
      case 'std_logic_vector(0 upto 3)':
         $variable_size = 4;
      break;
      case 'std_logic_vector(0 upto 7)':
         $variable_size = 8;
      break;
      case 'std_logic_vector(0 upto 15)':
         $variable_size = 16;
      break;
      case 'std_logic_vector(0 upto 31)':
         $variable_size = 32;
      break;
      default:
         $variable_size = 0;
      break;
   }
   $library .= "IEE.std_logic_unsigned.all;";  
   $variables .= "variable var_1 integer;";
   if($variable_size == 0){
      $variables .= "\nvariable var_2 std_logic;";
   }
   $variables .= "\nvariable var_2 std_logic_vector($variable_size downto 0);";
   $process .= "\nif (add_sub = '1') then\n var_1=conv_integer(input_1)+conv_integer(input_2);\n var_2=conv_std_logic_vector(var_1,var_2'length); \n sum =var_2(".($variable_size-1)." downto 0);\n cout=var_2($variable_size);";
   $process .= "\nelsif (add_sub = '0') then\n var_1=conv_integer(input_1)-conv_integer(input_2);\n var_2=conv_std_logic_vector(var_1,var_2'length);\n sum =var_2(".($variable_size-1)." downto 0);\n cout=var_2($variable_size);\nendif;";
}

//
if($variables == '' || $variables == null){
   $variables = '';
}
if($library == '' || $library == null){
   $library = '';
}



// ================ replace text
 $template_file = 'template.txt';
 $template_contents = file_get_contents($template_file);
 $template_contents = str_replace('{library}',$library, $template_contents);
 $template_contents = str_replace('{entityName}',$entityName , $template_contents);
 $template_contents = str_replace('{ports}', substr($ports,1), $template_contents);
 $template_contents = str_replace('{variables}',$variables, $template_contents);
 $template_contents = str_replace('{process_inputs}', implode(",", $input_array), $template_contents);
 $template_contents = str_replace('{process}', $process, $template_contents);


// ================= return the response
$json_response = $template_contents;
header('Content-Type: text/plain; charset=utf-8');
echo $json_response;



// =============== Deciamal To Binary Converter
function decimalToBinary($decimal) {
   $binary = "";
   while ($decimal > 0) {
     $binary = ($decimal % 2) . $binary;
     $decimal = floor($decimal / 2);
   }
   return $binary === "" ? "0" : $binary;
 }