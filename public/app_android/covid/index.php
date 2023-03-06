<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CodedThemes">
    <meta name="keywords" content=" Admin , Responsive, Landing, Bootstrap, App, Template, Mobile, iOS, Android, apple, creative app">
    <meta name="author" content="sheshu">
    <link rel="shortcut icon" href="https://www.guseducationindia.com/media/1003/favicon-96x96.png" type="image/x-icon">
    <title>GEI</title>
    <!-- Bootstrap core CSS -->
    <link href="landing/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts for this template -->
    <link href="landing/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
    <!-- Custom styles for this template -->
    <link href="landing/css/creative.min.css" rel="stylesheet">

    <!-- Bootstrap core JavaScript -->
    <script src="landing/vendor/jquery/jquery.min.js"></script>
    <script src="landing/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Plugin JavaScript -->
    <script src="landing/vendor/jquery-easing/jquery.easing.min.js"></script>

    <style type="text/css" media="screen">
    body{
    background: url('landing/img/login.jpg');
    background-attachment: fixed;
    background-size: cover;
    }
    </style>
  </head>
  <body id="page-top">
    
  <div class='container modal-content' style="margin-top: 24px;">

      <div class="modal-header" style="padding:17px 55px;background: mediumslateblue;text-transform: uppercase;color: white;">  
        <img style="width: 90px;" src="https://www.guseducationindia.com/media/1002/logo.png?anchor=center&mode=crop&width=180&height=103">
        <h4 style="margin-left: 10%;font-weight: 700;"><span class="glyphicon glyphicon-lock"></span>Employee Covid-19 Insurance Form</h4>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter" style="margin-right: -29px;">
          Covid-19 Insurance Premium Chart
        </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="width: 1000px;">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle" style="color:blue">Covid-19 Insurance Premium Chart</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
              <img src="corona_insurance.jpg" alt="" style="width: 587px;">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div><br>

  <form class="needs-validation1" id="covidForm"> <!-- novalidate   method='post' action=''-->
      <div class="form-row">
        <div class="col-md-4 mb-3">
          <label for="validationCustom01">EMP ID <span style="color:red">*</span></label>
          <input type="text" class="form-control" name='empid' id="validationCustom01" placeholder="EMP Id" required="required">
          <!-- <div class="valid-feedback">
            Looks good!
          </div> -->
        </div>
        <div class="col-md-4 mb-3">
          <label for="validationCustom01">First Name <span style="color:red">*</span></label>
          <input type="text" class="form-control" name='fname' id="validationCustom01" placeholder="First name" required>
          <!-- <div class="valid-feedback">
            Looks good!
          </div> -->
        </div>
        <div class="col-md-4 mb-3">
          <label for="validationCustom02">Last Name <span style="color:red">*</span></label>
          <input type="text" class="form-control" name='lname' id="validationCustom02" placeholder="Last name" required>
          <!-- <div class="valid-feedback">
            Looks good!
          </div> -->
        </div>
      </div>
      <div class="form-row">
      <div class="col-md-4 mb-3">
          <!-- <label for="validationCustomUsername">Email <span style="color:red">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" id="inputGroupPrepend">@</span>
            </div>
            <input type="email" class="form-control" name='email' id="validationCustomUsername" placeholder="Username" aria-describedby="inputGroupPrepend" required>
            <div class="invalid-feedback">
              Please provide an email.
            </div>
          </div> -->
      </div>
      <div class="col-md-4 mb-3">
        <label for="validationCustom05">Interested to Apply Covid-19 Insurance ? <span style="color:red">*</span></label>
        <select class="custom-select" id="interestedToApply" name='interestedToApply' required>
          <option value="">Select</option>
          <option value="YES">YES</option>
          <option value="NO">NO</option>
        </select>
        <!-- <div class="invalid-feedback">
          Please Select.
        </div> -->
      </div>
      <div class="col-md-4 mb-3">
       <!--  <label for="validationCustom04">Contact Number</label>
        <input type="text" class="form-control" name='phone' id="validationCustom04" placeholder="Contact Number" onkeypress="return isNumberKey(event)" required>
        <div class="invalid-feedback">
          Please provide a valid contact number.
        </div> -->
      </div>
      </div>
  
      <!-- EMP Details -->
    <div class="covid_details">
      <div class="form-row" style="border: 1px solid black;padding: 10px;margin-top: 18px;" id="covid_details">
        <div class="col-md-3 mb-3">
        <label for="validationCustom05">Select Self Sum Insured(&#8377)</label>
        <select class="custom-select" id="eamount" name='eamount' required onchange="premium('eprm')">
          <option value="">Select</option>
          <option value="50000">50,000</option>
          <option value="100000">1,00,000</option>
          <option value="150000">1,50,000</option>
          <option value="200000">2,00,000</option>
          <option value="250000">2,50,000</option>
          <option value="300000">3,00,000</option>
          <option value="350000">3,50,000</option>
          <option value="400000">4,00,000</option>
          <option value="450000">4,50,000</option>
          <option value="500000">5,00,000</option>
        </select>
        </div>
        <div class="col-md-3 mb-3">
        <label for="validationCustom05">Select Self Plan Tenture</label>
        <select class="custom-select" id="eplan" name='eplan' required onchange="premium('eprm')">
          <option value="">Select</option>
          <option value="3.5">3,1/2 Months</option>
          <option value="6.5">6,1/2 Months</option>
          <option value="9.5">9,1/2 Months</option>
        </select>
        </div>
        <div class="col-md-3 mb-3">
          <label for="validationCustom05">Select Your Age</label>
          <select class="custom-select" id="eage" name='eage' required onchange="premium('eprm')">
            <option value="">Select</option>
            <option value="upto40">Upto 40</option>
            <option value="41-60">41-60</option>
            <option value="61-65">60-65</option>
          </select>
          <!-- <div style="color:red">Note: Age not morethen 65</div>  -->
        </div>
        <div class="col-md-3 mb-3">
          <label for="validationCustom05">Self Premium(&#8377)</label>
          <input type="text" class="form-control" id="eprm" value="0" disabled>
        </div>
        <div class="col-md-4 mb-3">
          <label for="validationCustom05">Select Your DOB</label>
          <input type="date" class="form-control" id="edob" name='edob' required>
        </div>
        <div class="col-md-4 mb-3">
          <label for="nomine_name">Nominee Name</label>
          <input type="text" class="form-control" name='nomine_name' id="nomine_name" placeholder="Nominee Name" required="required">
        </div>
        <div class="col-md-4 mb-3">
          <label for="nomine_relation">Nominee Relation</label>
          <input type="text" class="form-control" name='nomine_relation' id="nomine_relation" placeholder="Nominee Relation" required="required">
        </div>

        <div class="col-md-3" style="margin-top: 29px;">
          <label for="validationCustom05">Add Spouse</label>
          <input id="spouse_tab" type="checkbox" name="spouse_tab" onchange="valueChanged()"/>
        </div>
        <div class="col-md-3 mb-3" style="margin-top: 29px;">
          <label for="validationCustom05">Add Parents</label>
          <input id="parents_tab" type="checkbox" name="parents_tab" onchange="valueChanged()"/>
        </div>
        <div class="col-md-3 mb-3" style="margin-top: 29px;">
          <label for="validationCustom05">Add Childrens</label>
          <input id="child_tab" type="checkbox" name="child_tab" onchange="valueChanged()"/>
        </div>
        <div class="col-md-3 mb-3" style="margin-top: 29px;">
          <label for="validationCustom05">Add In-Laws</label>
          <input id="inlaw_tab" type="checkbox" name="inlaw_tab" onchange="valueChanged()"/>
        </div>
      </div>

      <!-- spouse details -->
        <div class="form-row" style="border: 1px solid black;padding: 10px;margin-top: 18px;" id="spouse_details">
          <div class="col-md-4 mb-3">
            <label for="sname">Spouse Name</label>
            <input type="text" class="form-control" id="sname" name='sname' placeholder="Spouse Name" required>
          </div>
          <div class="col-md-4 mb-3">
            <label for="sdob">Spouse DOB</label>
            <input type="date" class="form-control" id="sdob" name='sdob' required>
          </div>
          <div class="col-md-4 mb-3">
          <label for="samount">Sum Insured(&#8377)</label>
          <select class="custom-select" id="samount" name='samount' required onchange="premium('sprm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-4 mb-3">
          <label for="splan">Plan Tenture</label>
          <select class="custom-select" id="splan" name='splan' required onchange="premium('sprm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-4 mb-3">
            <label for="sage">Age</label>
            <select class="custom-select" id="sage" name='sage' required onchange="premium('sprm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="61-65">61-65</option>
            </select>
            <!-- <div style="color:red">Note: Age not morethen 65</div> -->
          </div>
          <div class="col-md-4 mb-3">
           <label for="sprm">Premium(&#8377)</label>
             <input type="text" class="form-control" id="sprm" value="0" disabled>
          </div>
        </div>
        
        <!-- parents details -->
        <div class="form-row" style="border: 1px solid black;padding: 10px;margin-top: 18px;" id="parents_details">
          <div class="col-md-6 mb-3">
            <label for="mname">Mother Name</label>
            <input type="text" class="form-control" id="mname" name='mname' placeholder="Mother Name" >
          </div>
          <div class="col-md-6 mb-3">
            <label for="mdob">Mother DOB</label>
            <input type="date" class="form-control" id="mdob" name='mdob'>
          </div>
          <div class="col-md-3 mb-3">
          <label for="mamount">Sum Insured(&#8377)</label>
          <select class="custom-select" id="mamount" name='mamount' onchange="premium('mprm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-3 mb-3">
          <label for="mplan">Plan Tenture</label>
          <select class="custom-select" id="mplan" name='mplan' onchange="premium('mprm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-3 mb-3">
            <label for="mage">Age</label>
            <select class="custom-select" id="mage" name='mage' onchange="premium('mprm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="61-65">61-65</option>
            </select>
            </div>
            <!-- <div style="color:red">Note: Age not morethen 65</div> -->
            <div class="col-md-3 mb-3">
             <label for="mprm">Premium(&#8377)</label>
             <input type="text" class="form-control" id="mprm" value="0" disabled>
          </div>
        
          <div class="col-md-6 mb-3" style="border-top: 1px solid red;padding: 6px">
            <label for="faname">Father Name</label>
            <input type="text" class="form-control" id="faname" name='faname' placeholder="Father Name" >
          </div>
          <div class="col-md-6 mb-3" style="border-top: 1px solid red;padding: 6px">
            <label for="fdob">Father DOB</label>
            <input type="date" class="form-control" id="fdob" name='fdob'>
          </div>
          <div class="col-md-3 mb-3">
          <label for="famount">Sum Insured(&#8377)</label>
          <select class="custom-select" id="famount" name='famount' onchange="premium('fprm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-3 mb-3">
          <label for="fplan">Plan Tenture</label>
          <select class="custom-select" id="fplan" name='fplan' onchange="premium('fprm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-3 mb-3">
            <label for="fage">Age</label>
            <select class="custom-select" id="fage" name='fage' onchange="premium('fprm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="61-65">61-65</option>
            </select>
            <!-- <div style="color:red">Note: Age not morethen 65</div> -->
          </div>
          <div class="col-md-3 mb-3">
           <label for="fprm">Premium(INR)</label>
             <input type="text" class="form-control" id="fprm" value="0" disabled>
          </div>
        </div>
        
        <!-- childrens details -->
        <div class="form-row" style="border: 1px solid black;padding: 10px;margin-top: 18px;" id="child_details">
          <div class="col-md-2 mb-3">
            <label for="validationCustom04">Children 1 Name</label>
            <input type="text" class="form-control" id="c1name" name='c1name' placeholder="Children 1 Name" required>
          </div>
          <div class="col-md-3 mb-3">
            <label for="c1dob">Children 1 DOB</label>
            <input type="date" class="form-control" id="c1dob" name='c1dob' required>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Sum Insured(&#8377)</label>
          <select class="custom-select" id="c1amount" name='c1amount' required onchange="premium('c1prm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Plan Tenture</label>
          <select class="custom-select" id="c1plan" name='c1plan' required onchange="premium('c1prm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-2 mb-3">
            <label for="validationCustom05">Age</label>
            <select class="custom-select" id="c1age" name='c1age' required onchange="premium('c1prm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <!-- <option value="41-60">41-60</option>
              <option value="above60">Above 60</option> -->
            </select>
          </div>
          <div class="col-md-1 mb-3">
           <label for="validationCustom05">Premium(&#8377)</label>
             <input type="text" class="form-control" id="c1prm" value="0" disabled>
          </div>
        
          <div class="col-md-2 mb-3">
            <label for="validationCustom04">Children 2 Name</label>
            <input type="text" class="form-control" id="c2name" name='c2name' placeholder="Children 2 Name" >
          </div>
          <div class="col-md-3 mb-3">
            <label for="c2dob">Children 2 DOB</label>
            <input type="date" class="form-control" id="c2dob" name='c2dob'>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Sum Insured(&#8377)</label>
          <select class="custom-select" id="c2amount" name='c2amount' onchange="premium('c2prm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Plan Tenture</label>
          <select class="custom-select" id="c2plan" name='c2plan' onchange="premium('c2prm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-2 mb-3">
            <label for="validationCustom05">Age</label>
            <select class="custom-select" id="c2age" name='c2age' onchange="premium('c2prm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <!-- <option value="41-60">41-60</option>
              <option value="above60">Above 60</option> -->
            </select>
          </div>
          <div class="col-md-1 mb-3">
           <label for="validationCustom05">Premium(&#8377)</label>
             <input type="text" class="form-control" id="c2prm" value="0" disabled>
          </div>
        
          <div class="col-md-2 mb-3">
            <label for="validationCustom04">Children 3 Name</label>
            <input type="text" class="form-control" id="c3name" name='c3name' placeholder="Children 3 Name" >
          </div>
          <div class="col-md-3 mb-3">
            <label for="c3dob">Children 3 DOB</label>
            <input type="date" class="form-control" id="c3dob" name='c3dob'>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Sum Insured(&#8377)</label>
          <select class="custom-select" id="c3amount" name='c3amount' onchange="premium('c3prm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-2 mb-3">
          <label for="validationCustom05">Plan Tenture</label>
          <select class="custom-select" id="c3plan" name='c3plan' onchange="premium('c3prm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-2 mb-3">
            <label for="validationCustom05">Select Age</label>
            <select class="custom-select" id="c3age" name='c3age' onchange="premium('c3prm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <!-- <option value="41-60">41-60</option>
              <option value="above60">Above 60</option> -->
            </select>
          </div>
          <div class="col-md-1 mb-3">
           <label for="validationCustom05">Premium(&#8377)</label>
             <input type="text" class="form-control" id="c3prm" value="0" disabled>
          </div>
        
          <!-- <div class="col-md-3 mb-3">
            <label for="validationCustom04">Children 4 Name</label>
            <input type="text" class="form-control" id="c4name" name='c4name' placeholder="Children 4 Name" >
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Select Sum Insured (INR)</label>
          <select class="custom-select" id="c4amount" name='c4amount' >
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Select Plan Tenture</label>
          <select class="custom-select" id="c4plan" name='c4plan' >
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-3 mb-3">
            <label for="validationCustom05">Select Age</label>
            <select class="custom-select" id="c4age" name='c4age' >
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="above60">Above 60</option>
            </select>
          </div> -->
        </div>

        <!-- in-law details -->
        <div class="form-row" style="border: 1px solid black;padding: 10px;margin-top: 18px;" id="inlaw_details">
          <div class="col-md-6 mb-3">
            <label for="validationCustom04">Mother-in-Law Name</label> 
            <input type="text" class="form-control" id="milname" name='milname' placeholder="Mother-in-Law Name" >
          </div>
          <div class="col-md-6 mb-3">
            <label for="mildob">Mother-in-Law DOB</label>
            <input type="date" class="form-control" id="mildob" name='mildob'>
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Sum Insured(&#8377)</label>
          <select class="custom-select" id="milamount" name='milamount' onchange="premium('milprm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Plan Tenture</label>
          <select class="custom-select" id="milplan" name='milplan' onchange="premium('milprm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-3 mb-3">
            <label for="validationCustom05">Age</label>
            <select class="custom-select" id="milage" name='milage' onchange="premium('milprm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="61-65">61-65</option>
            </select>
            </div>
            <!-- <div style="color:red">Note: Age not morethen 65</div> -->
            <div class="col-md-3 mb-3">
             <label for="validationCustom05">Premium(&#8377)</label>
             <input type="text" class="form-control" id="milprm" value="0" disabled>
          </div>

          <div class="col-md-6 mb-3" style="border-top: 1px solid red;padding: 6px">
            <label for="validationCustom04">Father-In-Law Name</label>
            <input type="text" class="form-control" id="failname" name='failname' placeholder="Father-In-Law Name" >
          </div>
          <div class="col-md-6 mb-3" style="border-top: 1px solid red;padding: 6px">
            <label for="fildob">Father-In-Law DOB</label>
            <input type="date" class="form-control" id="fildob" name='fildob'>
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Sum Insured(&#8377)</label>
          <select class="custom-select" id="filamount" name='filamount' onchange="premium('filprm')">
            <option value="">Select</option>
            <option value="50000">50,000</option>
            <option value="100000">1,00,000</option>
            <option value="150000">1,50,000</option>
            <option value="200000">2,00,000</option>
            <option value="250000">2,50,000</option>
            <option value="300000">3,00,000</option>
            <option value="350000">3,50,000</option>
            <option value="400000">4,00,000</option>
            <option value="450000">4,50,000</option>
            <option value="500000">5,00,000</option>
          </select>
          </div>
          <div class="col-md-3 mb-3">
          <label for="validationCustom05">Plan Tenture</label>
          <select class="custom-select" id="filplan" name='filplan' onchange="premium('filprm')">
            <option value="">Select</option>
            <option value="3.5">3,1/2 Months</option>
            <option value="6.5">6,1/2 Months</option>
            <option value="9.5">9,1/2 Months</option>
          </select>
          </div> 
          <div class="col-md-3 mb-3">
            <label for="validationCustom05">Age</label>
            <select class="custom-select" id="filage" name='filage' onchange="premium('filprm')">
              <option value="">Select</option>
              <option value="upto40">Upto 40</option>
              <option value="41-60">41-60</option>
              <option value="61-65">61-65</option>
            </select>
            <!-- <div style="color:red">Note: Age not morethen 65</div> -->
          </div>
          <div class="col-md-3 mb-3">
           <label for="validationCustom05">Premium(&#8377)</label>
             <input type="text" class="form-control" id="filprm" value="0" disabled>
          </div>
        </div>
    </div>
     <br>
      <div class="form-group agree-section" style="text-align: center;">
        <div class="form-check">
          <input class="form-check-input agree" type="checkbox" name="agree" id="invalidCheck" required>
          <label class="form-check-label" for="invalidCheck">
            I agree to the terms of Salary deduction from GEI HR based on applied Insurance Premium for Covid-19
          </label>
          <div class="invalid-feedback">
            You must agree before submitting.
          </div>
          <br><br>
        <div class="form-check" id="total_amount">
          <label class="form-check-label" for="invalidCheck">Total Premium Amount (&#8377)<input class="form-control" type="text" id="total" disabled></label>
        </div>
        </div>
      </div>
      <div class="form-group agree-section" style="text-align: center;">
          <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary" >Submit</button>
          <button type="submit" name="btnsubmit2" id="btnsubmit2" class="btn btn-primary">Final Submit</button>
      </div>
  </form>

  <div class="form-group " style="margin-top: -13px;margin-bottom: -3px;">
    <span class="alert-danger " id="error_msg" style=""></span>
    <span class="alert-success" id="suss_msg" style="font-size: larger;margin-left: 120px;"></span>
  </div>

<script>

function isNumberKey(evt) 
{
    var charCode = (evt.which) ? evt.which : event.keyCode;
    if (charCode != 46 && charCode > 31 &&
        (charCode < 48 || charCode > 57)) {
        alert("Enter Number");
        return false;
    }
    return true;
}

$('#interestedToApply').on('change', function() {
  if ( this.value == 'YES'){
    $(".covid_details").show();
    $(".agree-section").show();  
  }else if ( this.value == 'NO'){
    $(".covid_details").hide();
    $(".agree-section").show();
  }else{
    $(".covid_details").hide();
    $(".agree-section").hide(); 
  }
}).trigger("change");

var $submit = $("#btnsubmit2").hide(); 
$("#total_amount").hide();

$cbs = $('input[name="agree"]').click(function() {

    var total=0;
    var eprm=0;
    var sprm=0;
    var pprm=0;
    var cprm=0;
    var inlawprm=0;

    if($cbs.is(":checked"))
    {
      
        if($('#interestedToApply').val()=='YES' && $('select[name=eamount]').val()!='' && $('select[name=eplan]').val()!='' && $('select[name=eage]').val()!='' && $('#eprm').val()!='' && $('input[name=edob]').val()!='' && $('input[name=nomine_name]').val()!='' && $('input[name=nomine_relation]').val()!='')
        { 

          eprm = parseInt($('#eprm').val());
          console.log('eprm '+eprm);
          if(isNaN(eprm)){
            alert("Please Check Self Premium Details");
            return false;
          }

          if($('#spouse_tab').is(":checked"))
          {
            sprm = parseInt($('#sprm').val());
            console.log('sprm '+sprm);
            if(isNaN(sprm)){
              alert("Please Check Spouse Premium Details");
              return false;
            }
          }
          if($('#parents_tab').is(":checked"))
          {
            pprm = parseInt($('#mprm').val())+parseInt($('#fprm').val())
            if(isNaN(pprm) || (parseInt($('#mprm').val())!=0 && ($('input[name=mname]').val()=='' || $('input[name=mdob]').val()=='')) || (parseInt($('#fprm').val())!=0 && ($('input[name=faname]').val()=='' || $('input[name=fdob]').val()==''))){
              alert("Please Check Parents Premium Details");
              return false;
            }
            console.log('pprm '+pprm);
          }
          if($('#inlaw_tab').is(":checked"))
          {
            inlawprm = parseInt($('#milprm').val())+parseInt($('#filprm').val())
            console.log('inlawprm '+inlawprm);
            if(isNaN(inlawprm) || (parseInt($('#milprm').val())!=0 && ($('input[name=milname]').val()=='' || $('input[name=mildob]').val()=='')) || (parseInt($('#filprm').val())!=0 && ($('input[name=failname]').val()=='' || $('input[name=fildob]').val()==''))){
              alert("Please Check In-Laws Premium Details");
              return false;
            }
          }
          if($('#child_tab').is(":checked"))
          {
            cprm = parseInt($('#c1prm').val())+parseInt($('#c2prm').val())+parseInt($('#c3prm').val());
            console.log('cprm '+cprm);
            if(isNaN(cprm)){
              alert("Please Check Children Premium Details");
              return false;
            }
          }
          var total = eprm+sprm+pprm+cprm+inlawprm;

          console.log('total '+total);
          if(total>0)
          {
            $("#total_amount").show();
            $('#total').val(total);
          }else{
            alert('Please Check all Details');
              return false;
          }

          //$('input.agree').not(this).prop('checked', false);
          $("#btnsubmit2").show();
          $("#btnsubmit").hide();

        }else if($('#interestedToApply').val()=='NO' && $('input[name=fname]').val()!='' && $('input[name=lname]').val()!='' && $('input[name=empid]').val()!=''){
          $("#btnsubmit2").show();
          $("#btnsubmit").hide();
        }else{
          $('input.agree').prop('checked', false);
          $("#btnsubmit").show();
          $("#btnsubmit2").hide();
        }
    }else{
      $("#total_amount").hide();
      $("#btnsubmit").show();  
      $("#btnsubmit2").hide();
      $('#total').val();
    }
    //enableDisableAll(this);
    //$submit.toggle( $cbs.is(":checked") );
});

valueChanged();
function valueChanged()
    {
        if($('#spouse_tab').is(":checked"))   
            $("#spouse_details").show();
        else
            $("#spouse_details").hide();

        if($('#parents_tab').is(":checked"))   
            $("#parents_details").show();
        else
            $("#parents_details").hide();

        if($('#child_tab').is(":checked"))   
            $("#child_details").show();
        else
            $("#child_details").hide();

        if($('#inlaw_tab').is(":checked"))   
            $("#inlaw_details").show();
        else
            $("#inlaw_details").hide();

        $('input.agree').prop('checked', false);
        $("#total_amount").hide();
        $("#btnsubmit").show();  
        $("#btnsubmit2").hide();
        $('#total').val();
    }



// Example starter JavaScript for disabling form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();

/*function enableDisableAll(e) {
        var own = e;
        var form = document.getElementById("covidForm");
        var elements = form.elements;

    for (var i = 0 ; i < elements.length ; i++) {
          if(own !== elements[i] ){
          
            if(own.checked == true){
              
              elements[i].disabled = true;  
             
            }else{
            
              elements[i].disabled = false;  
            }
          
           }

     } 
}*/

$(document).ready(function()
{     
  $('#covidForm').submit(function(e){
          e.preventDefault()
          //alert($(this).serialize())
  });

      $("#btnsubmit2").click(function(e)
      { 
        
        var intersted = $('select[name=interestedToApply]').val();

           if(intersted=='YES')
           {
              if($('#spouse_tab').is(":checked"))
               {  

                  if($('input[name=sname]').val()!='' && $('select[name=samount]').val()!='' && $('select[name=splan]').val()!='' && $('select[name=sage]').val()!='' && $('input[name=sdob]').val()!='')
                  {
                      var spouse = {
                      sname: $('input[name=sname]').val(),
                      samount: $('select[name=samount]').val(),
                      splan:$('select[name=splan]').val(),
                      sage: $('select[name=sage]').val(),
                      sprm: $('#sprm').val(),
                      sdob: $('input[name=sdob]').val()
                    }
                  }else{
                    alert("Please Check Spouse Details Or Uncheck Spouse Tab");
                    return false;
                  }                  
               }else{
                var spouse = null;
               }

               if($('#parents_tab').is(":checked"))
               {  
                  var mtr=0,ftr=0;
                  if($('input[name=mname]').val()!='' && $('select[name=mamount]').val()!='' && $('select[name=mplan]').val()!='' && $('select[name=mage]').val()!='' && $('input[name=mdob]').val()!='')
                  {
                    mtr++;
                  }

                  if($('input[name=faname]').val()!='' && $('select[name=famount]').val()!='' && $('select[name=fplan]').val()!='' && $('select[name=fage]').val()!='' && $('input[name=fdob]').val()!='')
                  {
                    ftr++;
                  }

                  if(mtr>0 || ftr>0)
                  {
                      var parents = {
                      mname: $('input[name=mname]').val(),
                      mamount: $('select[name=mamount]').val(),
                      mplan:$('select[name=mplan]').val(),
                      mage: $('select[name=mage]').val(),
                      mprm: $('#mprm').val(),
                      mdob: $('input[name=mdob]').val(),
                      faname: $('input[name=faname]').val(),
                      famount: $('select[name=famount]').val(),
                      fplan:$('select[name=fplan]').val(),
                      fage: $('select[name=fage]').val(),
                      fprm: $('#fprm').val(),
                      fdob: $('input[name=fdob]').val()
                    }
                  }else{
                    alert("Please Check Parent Details Or Uncheck Parents Tab");
                    return false;
                  }
                  
               }else{
                var parents = null;
               }

               if($('#inlaw_tab').is(":checked"))
               {  
                  var miltr=0,filtr=0;
                  if($('input[name=milname]').val()!='' && $('select[name=milamount]').val()!='' && $('select[name=milplan]').val()!='' && $('select[name=milage]').val()!='' && $('input[name=mildob]').val()!='')
                  {
                    miltr++;
                  }

                  if($('input[name=failname]').val()!='' && $('select[name=filamount]').val()!='' && $('select[name=filplan]').val()!='' && $('select[name=filage]').val()!='' && $('input[name=fildob]').val()!='')
                  {
                    filtr++;
                  }

                  if(miltr>0 || filtr>0)
                  {
                      var inlaws = {
                        milname: $('input[name=milname]').val(),
                        milamount: $('select[name=milamount]').val(),
                        milplan:$('select[name=milplan]').val(),
                        milage: $('select[name=milage]').val(),
                        milprm: $('#milprm').val(),
                        mildob: $('input[name=mildob]').val(),
                        failname: $('input[name=failname]').val(),
                        filamount: $('select[name=filamount]').val(),
                        filplan:$('select[name=filplan]').val(),
                        filage: $('select[name=filage]').val(),
                        filprm: $('#filprm').val(),
                        fildob: $('input[name=fildob]').val()
                      }
                  }else{
                    alert("Please Check In-laws Details Or Uncheck In-laws Tab");
                    return false;
                  }

               }else{
                var inlaws = null;
               }

               if($('#child_tab').is(":checked"))
               {  
                  if($('input[name=c1name]').val()!='' && $('select[name=c1amount]').val()!='' && $('select[name=c1plan]').val()!='' && $('select[name=c1age]').val()!='' && $('input[name=c1dob]').val()!='')
                  {
                      var child = {
                      c1name: $('input[name=c1name]').val(),
                      c1amount: $('select[name=c1amount]').val(),
                      c1plan:$('select[name=c1plan]').val(),
                      c1age: $('select[name=c1age]').val(),
                      c1prm: $('#c1prm').val(),
                      c1dob: $('input[name=c1dob]').val(),
                      c2name: $('input[name=c2name]').val(),
                      c2amount: $('select[name=c2amount]').val(),
                      c2plan:$('select[name=c2plan]').val(),
                      c2age: $('select[name=c2age]').val(),
                      c2prm: $('#c2prm').val(),
                      c2dob: $('input[name=c2dob]').val(),
                      c3name: $('input[name=c3name]').val(),
                      c3amount: $('select[name=c3amount]').val(),
                      c3plan:$('select[name=c3plan]').val(),
                      c3age: $('select[name=c3age]').val(),
                      c3prm: $('#c3prm').val(),
                      c3dob: $('input[name=c3dob]').val()
                      /*c4name: $('input[name=c4name]').val(),
                      c4amount: $('select[name=c4amount]').val(),
                      c4plan:$('select[name=c4plan]').val(),
                      c4age: $('select[name=c4age]').val()*/
                    }
                  }else{
                    alert("Please Check Childrens Details Or Uncheck Childrens Tab");
                    return false;
                  }
                  
               }else{
                var child = null;
               }

               //final data
              var emp_details = {
                eamount: $('select[name=eamount]').val(),
                eplan:$('select[name=eplan]').val(),
                eage: $('select[name=eage]').val(),
                eprm: $('#eprm').val(),
                edob: $('input[name=edob]').val(),
                nomine_name: $('input[name=nomine_name]').val(),
                nomine_relation: $('input[name=nomine_relation]').val(),
                spouse: spouse,
                parents: parents,
                child: child,
                inlaws:inlaws
              }
           }else{
            var emp_details = null;
           }

        var person = {
            fname: $('input[name=fname]').val(),
            lname:$('input[name=lname]').val(),
            //email: $('input[name=email]').val(),
            empid: $('input[name=empid]').val(),
            //phone: $('input[name=phone]').val(),
            interestedToApply: $('select[name=interestedToApply]').val(),
            agree: $('input[name=agree]').val(),
            details: emp_details,
            total_premium: $('#total').val(),
        }

        console.log(person);

        $('#btnsubmit2').append('<i class="fa fa-spinner fa-spin" style="font-size:24px"></i>Processing..');

        $.ajax({
            url: '/covid/store.php',
            type: 'post',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(person),
            success: function (data) {
                console.log(data);
                if(data.status==true)
                {  
                   $('#covidForm').hide();
                   $('#suss_msg').html("<br>Thank You ! <br> Your details added Successfully.<br><br>");
                   $('#btnsubmit2').text('Done');
                }else{
                  //$('#covidForm').hide();
                  $('#btnsubmit2').text('Final Submit');
                  alert('Something went wrong, please check details');
                  //$("#error_msg").html('<br>Something went wrong, Please try again later');
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
        console.log('done');
      });
});

function premium(value)
{
 
 $('input.agree').prop('checked', false);
  $("#total_amount").hide();
  $("#btnsubmit").show();  
  $("#btnsubmit2").hide();
  $('#total').val();

 if(value=='eprm')
 {
    var a = $('select[name=eamount]').val()+'_'+$('select[name=eplan]').val()+'_'+$('select[name=eage]').val();
 }
 else if(value=='sprm')
 {
    var a = $('select[name=samount]').val()+'_'+$('select[name=splan]').val()+'_'+$('select[name=sage]').val();
 }
 else if(value=='mprm')
 {
    var a = $('select[name=mamount]').val()+'_'+$('select[name=mplan]').val()+'_'+$('select[name=mage]').val();
 }
 else if(value=='fprm')
 {
    var a = $('select[name=famount]').val()+'_'+$('select[name=fplan]').val()+'_'+$('select[name=fage]').val();
 }
 else if(value=='c1prm')
 {
    var a = $('select[name=c1amount]').val()+'_'+$('select[name=c1plan]').val()+'_'+$('select[name=c1age]').val();
 }
 else if(value=='c2prm')
 {
    var a = $('select[name=c2amount]').val()+'_'+$('select[name=c2plan]').val()+'_'+$('select[name=c2age]').val();
 }
 else if(value=='c3prm')
 {
    var a = $('select[name=c3amount]').val()+'_'+$('select[name=c3plan]').val()+'_'+$('select[name=c3age]').val();
 }else if(value=='milprm')
 {
    var a = $('select[name=milamount]').val()+'_'+$('select[name=milplan]').val()+'_'+$('select[name=milage]').val();
 }
 else if(value=='filprm')
 {
    var a = $('select[name=filamount]').val()+'_'+$('select[name=filplan]').val()+'_'+$('select[name=filage]').val();
 }
 console.log("selection "+a);


  var day=null;
  switch (a) {
    case '__':
      day = 0;
      break;
    case '50000_9.5_upto40':
      day = 257;
      break;
    case '50000_9.5_41-60':
      day = 343;
      break;
    case '50000_9.5_61-65':
      day = 514;
      break;
    case '50000_6.5_upto40':
      day = 208;
      break;
    case '50000_6.5_41-60':
      day = 277;
      break;
    case '50000_6.5_61-65':
      day = 416;
      break;
    case '50000_3.5_upto40':
      day = 127;
      break;
    case '50000_3.5_41-60':
      day = 170;
      break;
    case '50000_3.5_61-65':
      day = 254;
      break;

    case '100000_9.5_upto40':
      day = 434;
      break;
    case '100000_9.5_41-60':
      day = 579;
      break;
    case '100000_9.5_61-65':
      day = 868;
      break;
    case '100000_6.5_upto40':
      day = 351;
      break;
    case '100000_6.5_41-60':
      day = 468;
      break;
    case '100000_6.5_61-65':
      day = 701;
      break;
    case '100000_3.5_upto40':
      day = 215;
      break;
    case '100000_3.5_41-60':
      day = 286;
      break;
    case '100000_3.5_61-65':
      day = 429;
      break;

    case '150000_9.5_upto40':
      day = 595;
      break;
    case '150000_9.5_41-60':
      day = 793;
      break;
    case '150000_9.5_61-65':
      day = 1189;
      break;
    case '150000_6.5_upto40':
      day = 481;
      break;
    case '150000_6.5_41-60':
      day = 641;
      break;
    case '150000_6.5_61-65':
      day = 961;
      break;
    case '150000_3.5_upto40':
      day = 294;
      break;
    case '150000_3.5_41-60':
      day = 392;
      break;
    case '150000_3.5_61-65':
      day = 588;
      break;

    case '200000_9.5_upto40':
      day = 743;
      break;
    case '200000_9.5_41-60':
      day = 990;
      break;
    case '200000_9.5_61-65':
      day = 1485;
      break;
    case '200000_6.5_upto40':
      day = 600;
      break;
    case '200000_6.5_41-60':
      day = 800;
      break;
    case '200000_6.5_61-65':
      day = 1200;
      break;
    case '200000_3.5_upto40':
      day = 367;
      break;
    case '200000_3.5_41-60':
      day = 490;
      break;
    case '200000_3.5_61-65':
      day = 735;
      break;

    case '250000_9.5_upto40':
      day = 876;
      break;
    case '250000_9.5_41-60':
      day = 1168;
      break;
    case '250000_9.5_61-65':
      day = 1752;
      break;
    case '250000_6.5_upto40':
      day = 708;
      break;
    case '250000_6.5_41-60':
      day = 944;
      break;
    case '250000_6.5_61-65':
      day = 1416;
      break;
    case '250000_3.5_upto40':
      day = 433;
      break;
    case '250000_3.5_41-60':
      day = 578;
      break;
    case '250000_3.5_61-65':
      day = 867;
      break;

    case '300000_9.5_upto40':
      day = 932;
      break;
    case '300000_9.5_41-60':
      day = 1243;
      break;
    case '300000_9.5_61-65':
      day = 1864;
      break;
    case '300000_6.5_upto40':
      day = 753;
      break;
    case '300000_6.5_41-60':
      day = 1005;
      break;
    case '300000_6.5_61-65':
      day = 1507;
      break;
    case '300000_3.5_upto40':
      day = 461;
      break;
    case '300000_3.5_41-60':
      day = 615;
      break;
    case '300000_3.5_61-65':
      day = 923;
      break;

    case '350000_9.5_upto40':
      day = 1061;
      break;
    case '350000_9.5_41-60':
      day = 1414;
      break;
    case '350000_9.5_61-65':
      day = 2122;
      break;
    case '350000_6.5_upto40':
      day = 857;
      break;
    case '350000_6.5_41-60':
      day = 1143;
      break;
    case '350000_6.5_61-65':
      day = 1715;
      break;
    case '350000_3.5_upto40':
      day = 525;
      break;
    case '350000_3.5_41-60':
      day = 700;
      break;
    case '350000_3.5_61-65':
      day = 1050;
      break;

    case '400000_9.5_upto40':
      day = 1125;
      break;
    case '400000_9.5_41-60':
      day = 1500;
      break;
    case '400000_9.5_61-65':
      day = 2250;
      break;
    case '400000_6.5_upto40':
      day = 909;
      break;
    case '400000_6.5_41-60':
      day = 1212;
      break;
    case '400000_6.5_61-65':
      day = 1819;
      break;
    case '400000_3.5_upto40':
      day = 557;
      break;
    case '400000_3.5_41-60':
      day = 742;
      break;
    case '400000_3.5_61-65':
      day = 1113;
      break;

    case '450000_9.5_upto40':
      day = 1246;
      break;
    case '450000_9.5_41-60':
      day = 1661;
      break;
    case '450000_9.5_61-65':
      day = 2491;
      break;
    case '450000_6.5_upto40':
      day = 1007;
      break;
    case '450000_6.5_41-60':
      day = 1342;
      break;
    case '450000_6.5_61-65':
      day = 2013;
      break;
    case '450000_3.5_upto40':
      day = 616;
      break;
    case '450000_3.5_41-60':
      day = 822;
      break;
    case '450000_3.5_61-65':
      day = 1233;
      break;

    case '500000_9.5_upto40':
      day = 1286;
      break;
    case '500000_9.5_41-60':
      day = 1714;
      break;
    case '500000_9.5_61-65':
      day = 2572;
      break;
    case '500000_6.5_upto40':
      day = 1039;
      break;
    case '500000_6.5_41-60':
      day = 1386;
      break;
    case '500000_6.5_61-65':
      day = 2078;
      break;
    case '500000_3.5_upto40':
      day = 636;
      break;
    case '500000_3.5_41-60':
      day = 848;
      break;
    case '500000_3.5_61-65':
      day = 1272;
      break;
    default: 
      day = NaN;
  }

  if(value=='eprm')
 {
      $('#eprm').val(day);
 }
 else if(value=='sprm')
 {
    $('#sprm').val(day);
 }
 else if(value=='mprm')
 {
    $('#mprm').val(day);
 }
 else if(value=='fprm')
 {
    $('#fprm').val(day);
 }
 else if(value=='c1prm')
 {
    $('#c1prm').val(day);
 }
 else if(value=='c2prm')
 {
    $('#c2prm').val(day);
 }
 else if(value=='c3prm')
 {
    $('#c3prm').val(day);
 }
 else if(value=='milprm')
 {
    $('#milprm').val(day);
 }
 else if(value=='filprm')
 {
    $('#filprm').val(day);
 }
  console.log(day);
}
</script>  
</div>
</body>

</body>
</html>

