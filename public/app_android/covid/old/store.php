<?php
/* include database file  */
include "config.php";

if($_SERVER['REQUEST_METHOD'] == "POST")
{
	// Get post data`
	$data = json_decode(file_get_contents("php://input"));

	$firstName = mysqli_real_escape_string($conn,$data->fname);
	$lastName = mysqli_real_escape_string($conn,$data->lname);
	//$email = mysqli_real_escape_string($conn,$data->email);
	//$phone = mysqli_real_escape_string($conn,$data->phone); 
	$empid = mysqli_real_escape_string($conn,$data->empid);
	$agree = mysqli_real_escape_string($conn,$data->agree);
	$total_premium = mysqli_real_escape_string($conn,$data->total_premium);
	$agree = ($agree=='on')?'yes':'no';
	$interestedToApply = mysqli_real_escape_string($conn,$data->interestedToApply);

	if($firstName == '' || $lastName == '' || $empid == '' || $agree == '' || $interestedToApply == ''){  //$email == '' || $phone == ''
		$data = array("status"=>false,"msg" => "Please Fill Required Fileds");
		echo json_encode($data);
		exit;
	}
    
	if($interestedToApply=='YES' && $data->details!=null)
	{   
        $emp_sum_insured = $data->details->eamount;
        $emp_plan = $data->details->eplan.' Months';
        $emp_age = $data->details->eage;
        $eprm = $data->details->eprm;

        if($data->details->spouse!=null)
		{
            $sname = $data->details->spouse->sname;
            $samount = $data->details->spouse->samount;
            $splan = $data->details->spouse->splan.' Months';
            $sage = $data->details->spouse->sage;
            $sprm = $data->details->spouse->sprm;
		}else{
			$sname = null;
            $samount = null;
            $splan = null;
            $sage = null;
            $sprm = null;
		}

		if($data->details->parents!=null)
		{
            $mname = $data->details->parents->mname;
            $mamount = $data->details->parents->mamount;
            $mplan = ($data->details->parents->mplan!='')?$data->details->parents->mplan.' Months':null;
            $mage = $data->details->parents->mage;
            $mprm = $data->details->parents->mprm;

            $faname = $data->details->parents->faname;
            $famount = $data->details->parents->famount;
            $fplan = ($data->details->parents->fplan!='')?$data->details->parents->fplan.' Months':null;
            $fage = $data->details->parents->fage;
            $fprm = $data->details->parents->fprm;
		}else{
			$mname = null;
            $mamount = null;
            $mplan = null;
            $mage = null;
            $mprm = null;

            $faname = null;
            $famount = null;
            $fplan = null;
            $fage = null;
            $fprm = null;
		}

		if($data->details->child!=null)
		{
            $c1name = $data->details->child->c1name;
            $c1amount = $data->details->child->c1amount;
            $c1plan = $data->details->child->c1plan.' Months';
            $c1age = $data->details->child->c1age;
            $c1prm = $data->details->child->c1prm;

            $c2name = $data->details->child->c2name;
            $c2amount = $data->details->child->c2amount;
            $c2plan = ($data->details->child->c2plan!='')?$data->details->child->c2plan.' Months':null;
            $c2age = $data->details->child->c2age;
            $c2prm = $data->details->child->c2prm;

            $c3name = $data->details->child->c3name;
            $c3amount = $data->details->child->c3amount;
            $c3plan = ($data->details->child->c3plan!='')?$data->details->child->c3plan.' Months':null;
            $c3age = $data->details->child->c3age;
            $c3prm = $data->details->child->c3prm;

            /*$c4name = $data->details->child->c4name;
            $c4amount = $data->details->child->c4amount;
            $c4plan = ($data->details->child->c4plan!='')?$data->details->child->c4plan.' Months':null;
            $c4age = $data->details->child->c4age;*/
		}else{
			$c1name = null;
            $c1amount = null;
            $c1plan = null;
            $c1age = null;
            $c1prm = null;

            $c2name = null;
            $c2amount = null;
            $c2plan = null;
            $c2age = null;
            $c2prm = null;

            $c3name = null;
            $c3amount = null;
            $c3plan = null;
            $c3age = null;
            $c3prm = null;

            /*$c4name = null;
            $c4amount = null;
            $c4plan = null;
            $c4age = null;*/
		}

		$query = "INSERT INTO users (fname,lname,empid,agree,interestedToApply,total_premium,emp_sum_insured,emp_plan,emp_age,spouse_name,spouse_insured_amount,spouse_plan,spouse_age,m_name,m_insured_amount,m_plan,m_age,f_name,f_insured_amount,f_plan,f_age,c1_name,c1_insured_amount,c1_plan,c1_age,c2_name,c2_insured_amount,c2_plan,c2_age,c3_name,c3_insured_amount,c3_plan,c3_age,eprm,sprm,mprm,fprm,c1prm,c2prm,c3prm) VALUES ('$firstName','$lastName','$empid','$agree','$interestedToApply','$total_premium','$emp_sum_insured','$emp_plan','$emp_age','$sname','$samount','$splan','$sage','$mname','$mamount','$mplan','$mage','$faname','$famount','$fplan','$fage','$c1name','$c1amount','$c1plan','$c1age','$c2name','$c2amount','$c2plan','$c2age','$c3name','$c3amount','$c3plan','$c3age','$eprm','$sprm','$mprm','$fprm','$c1prm','$c2prm','$c3prm')";  

		$insert = mysqli_query($conn,$query);
		if($insert){
		$data = array("status"=>true,"msg" => "Successfully added!");
		} else {
		$data = array("status"=>false,"msg" => "Error!");
		}  
	}else{
		$query = "INSERT INTO users (fname,lname,empid,agree,interestedToApply,total_premium) VALUES ('$firstName','$lastName','$empid','$agree','$interestedToApply','$total_premium')";

		$insert = mysqli_query($conn,$query);
		if($insert){
		$data = array("status"=>true,"msg" => "Successfully added!");
		} else {
		$data = array("status"=>false,"msg" => "Error!");
		}
	}	
} else {
    $data = array("status"=>false,"msg" => "Request method is wrong!");
}

mysqli_close($conn);
/* JSON Response */
header('Content-type: application/json');
echo json_encode($data);

?>