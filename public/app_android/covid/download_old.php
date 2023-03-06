<?php
include "config.php";

$filename = "GEI_COVID_INSURANCE_DETAILS.csv";

//get records from database
$query = "select * from users";

$query_res = mysqli_query($conn,$query);


if($query_res->num_rows > 0)
{   
 
    $delimiter = ",";
    
    //create a file pointer
    $f = fopen('php://memory', 'w');
    
    //set column headers
    $fields = array('EMPID', 'First_Name', 'Last_Name','Interested to Apply Covid-19 Insurance ?','Agree HR Policy','Emp_sum_Insured','Emp_Plan','Emp_Age','Emp_DOB','Nominee_Name','Nominee_Relation','Spouse_Name','Spouse_DOB','Spouse_Insured_Amount','Spouse_Plan','Spouse_Age','Mother_Name','Mother_DOB','Mother_Insured_Amount','Mother_Plan','Mother_Age','Father_Name','Father_DOB','Father_Insured_Amount','Father_Plan','Father_Age','Children1_Name','Children1_DOB','Children1_Insured_Amount','Children1_Plan','Children1_Age','Children2_Name','Children2_DOB','Children2_Insured_Amount','Children2_Plan','Children2_Age','Children3_Name','Children3_DOB','Children3_Insured_Amount','Children3_Plan','Children3_Age','Mother-in-law_Name','Mother-in-law_DOB','Mother-in-law_Insured_Amount','Mother-in-law_Plan','Mother-in-law_Age','Father-in-law_Name','Father-in-law_DOB','Father-in-law_Insured_Amount','Father-in-law_Plan','Father-in-law_Age','EMP_Premium_Amount','Spouse_Premium_Amount','Mother_Premium_Amount','Father_Premium_Amount','Children1_Premium_Amount','Children2_Premium_Amount','Children3_Premium_Amount','Mother-in-law_Premium_Amount','Father-in-law_Premium_Amount','Total_Premium_Amount_Deduction');
    fputcsv($f, $fields, $delimiter);
    
    //output each row of the data, format line as csv and write to file pointer
    while($row = $query_res->fetch_assoc())
    {   
        $total_premium =  (int)$row['eprm']+(int)$row['sprm']+(int)$row['mprm']+(int)$row['fprm']+(int)$row['c1prm']+(int)$row['c2prm']+(int)$row['c3prm']+(int)$row['milprm']+(int)$row['filprm'];

        $lineData = array($row['empid'], $row['fname'], $row['lname'], $row['interestedToApply'], $row['agree'],$row['emp_sum_insured'],$row['emp_plan'],$row['emp_age'],$row['edob'],$row['nominee_name'],$row['nominee_relation'],$row['spouse_name'],$row['sdob'],$row['spouse_insured_amount'],$row['spouse_plan'],$row['spouse_age'],$row['m_name'],$row['mdob'],$row['m_insured_amount'],$row['m_plan'],$row['m_age'],$row['f_name'],$row['fdob'],$row['f_insured_amount'],$row['f_plan'],$row['f_age'],$row['c1_name'],$row['c1dob'],$row['c1_insured_amount'],$row['c1_plan'],$row['c1_age'],$row['c2_name'],$row['c2dob'],$row['c2_insured_amount'],$row['c2_plan'],$row['c2_age'],$row['c3_name'],$row['c3dob'],$row['c3_insured_amount'],$row['c3_plan'],$row['c3_age'],$row['mil_name'],$row['mildob'],$row['mil_insured_amount'],$row['mil_plan'],$row['mil_age'],$row['fil_name'],$row['fildob'],$row['fil_insured_amount'],$row['fil_plan'],$row['fil_age'],(int)$row['eprm'],(int)$row['sprm'],(int)$row['mprm'],(int)$row['fprm'],(int)$row['c1prm'],(int)$row['c2prm'],(int)$row['c3prm'],(int)$row['milprm'],(int)$row['filprm'],$total_premium);
        fputcsv($f, $lineData, $delimiter);
    }

    //move back to beginning of file
    fseek($f, 0);
    
    //set headers to download file rather than displayed
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    //output all remaining data on a file pointer
    fpassthru($f);

}else{
    echo 'Sorry, Data is Empty';
}
//echo "Done";
exit;

?>