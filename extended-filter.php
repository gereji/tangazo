<?php
	require_once('includes.php');
	$from = isset($_POST['from']) ? $_POST['from'] : date('Y-m-d', (time() - 7*24*60*60));
	$to = isset($_POST['to']) ? $_POST['to'] : date('Y-m-d');
	$companyFilter = isset($_POST['company']) && strlen(trim($_POST['company'])) ? trim($_POST['company']) : null;
	$mediaFilter = isset($_POST['media']) && strlen(trim($_POST['media'])) ? trim($_POST['media']) : null;
	$query[] = 'SELECT `accounts`.`name` AS `company`, `msa_campaign`.`companyCode`, `msa_brand`.`name` AS `brand`, `brandCode`, `msa_media`.`name` AS `media`, `mediaCode`, SUM(`msa_campaign`.`amount`) AS `amount`';
	$query[] = 'FROM `msa_campaign`';
	$query[] = 'JOIN `accounts` ON (`msa_campaign`.`companyCode` = `accounts`.`code`)';
	$query[] = 'JOIN `msa_brand` ON (`msa_campaign`.`brandCode` = `msa_brand`.`code`)';
	$query[] = 'JOIN `msa_media` ON (`msa_campaign`.`mediaCode` = `msa_media`.`code`)';
	$query[] = 'WHERE `msa_campaign`.`amount` > 0';
	$query[] = sprintf("AND `startDate` BETWEEN '%s' AND '%s'", $from, $to);
	if(!is_null($companyFilter)){
		$keywords = explode(',', $companyFilter);
		foreach($keywords as $keyword){
			$companyQuery[] = sprintf("MATCH (`accounts`.`name`) AGAINST('+%s*' IN BOOLEAN MODE)", mysqli_real_escape_string($dbConnection, trim($keyword)));
		}
		$query[] = sprintf("AND (%s)", implode(' OR ', $companyQuery));
	}
	if(!is_null($mediaFilter)){
		$keywords = explode(',', $mediaFilter);
		foreach($keywords as $keyword){
			$mediaQuery[] = sprintf("MATCH (`msa_media`.`name`) AGAINST('+%s*' IN BOOLEAN MODE)", mysqli_real_escape_string($dbConnection, trim($keyword)));
		}
		$query[] = sprintf("AND (%s)", implode(' OR ', $mediaQuery));
	}
	$query[] = 'GROUP BY `brandCode`, `mediaCode`';
	$query[] = 'ORDER BY `amount` DESC';
	$records = dbFetch(dbQuery(implode(' ', $query)));
	if(!is_null($records)){
		foreach($records as $record){
			$companyCode = $record['companyCode'];
			$brandCode = $record['brandCode'];
			$mediaCode = $record['mediaCode'];
			$company = $record['company'];
			$companies[$companyCode] = $company;
			$brands[$companyCode][$brandCode] = $record['brand'];
			$outlets[$mediaCode] = $record['media'];
			$spending[$companyCode][$brandCode][$mediaCode] = $record['amount'];
		}
	}		
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href="style.css" media="all" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>
<style type="text/css">
.header {
	display:block;
	width:100%;
	float:left;
}
.report-content .row {
	padding:0;
	margin:0;
}
.report-content {
	float: left;
	background-color: #FFF;
	display: block;
}

.report-content .header .column {
	font-weight: bold;
}

.report-content .header .column,.report-content .column:first-child {
	font-size: .75em;
	color:#444;
	text-transform:capitalize;
}

.report-content .row {
	border-bottom: 1px solid #DDD;
}

.report-content .column {
	padding: 10px 5px;
	overflow: hidden;
	width: 160px;
	text-align: right;
	border-left: 1px solid #DDD;
	overflow: hidden;
	margin-bottom:-99px;
	padding-bottom:99px;
}

.report-content .column:first-child {
	width: 240px;
	text-align: left;
	border-left: 0 none;
}
.report-content .row:first-child .column {
	border-left: 1px solid transparent;
}
.company-row {
	background-color:#EEE;
}
button,input[type="text"] {
	padding:5px;
}
input[type="text"] {
	border:1px solid #CCC;
}
.ui-datepicker-div {
	font-size:.75em;
}
</style>
<title>Media Spending :: Extended Company Report</title>
</head>
<body>
	<div class="header row">
		<form action="extended-filter.php" method="post" enctype="application/x-www-form-urlencoded">
			<h1 style="text-align:left;">Spending	by <input type="text" size="16" name="company" value="<?php print $companyFilter?>" placeholder="all companies"/> on <input type="text" name="media" value="<?php print $mediaFilter?>" placeholder="all media outlets"/> between	<input type="text" size="10" name="from" value="<?php print $from?>" class="datepicker" placeholder="<?php print $from?>"/>	and	<input type="text" size="10" name="to" value="<?php print $to?>" class="datepicker" placeholder="<?php print $to?>"/> &#160; <button type="submit">FILTER</button></h1>
		</form>
		<ul class="column grid10of10">
			<li><a href="index.php">Upload</a></li>
			<li><a href="filter.php">Filter</a></li>
			<li><a href="extended-filter.php" class="current">Extended Filter</a></li>
			<li><a href="spending.php">Spending</a></li>
			<li><a href="companies.php">Companies</a></li>
			<li><a href="brands.php">Brands</a></li>
			<li><a href="media.php">Media</a></li>
			<li><a href="sections.php">Sections</a></li>
			<li><a href="subsections.php">Sub Sections</a></li>
		</ul>
	</div>
	<?php
	if(!is_null($records)) {
		?>
		<div class="report-content" style="width:<?php print (251 + (count($outlets) * 171))?>px;">
			<div class="header">
				<div class="row">
					<div class="column"><strong>Brands</strong></div>
					<?php foreach($outlets as $mediaCode => $outlet){?>
						<div class="column" mediacode="<?php print $mediaCode?>">
							<?php print $outlet?> [<a href="javascript:removeColumn('<?php print $mediaCode?>')" title="Remove this column">X</a>]
						</div>
					<?php }?>
				</div>
				<div class="row">
					<div class="column">&#160;</div>
					<?php foreach($outlets as $mediaCode => $outlet){?>
						<div class="column" mediacode="<?php print $mediaCode?>">
							<?php
								$mediaTotal = 0;
								foreach ($spending as $companySpending){
									foreach($companySpending as $brandSpending){
										@$mediaTotal += $brandSpending[$mediaCode];
									}
								}
								print number_format($mediaTotal);
							?>
						</div>
					<?php }?>
				</div>
			</div>
			<?php foreach($brands as $companyCode => $companyBrands){?>
				<?php foreach ($companyBrands as $brandCode => $brand){?>
					<div class="row">
						<div class="column">
							<?php print strtolower($companyBrands[$brandCode])?>
						</div>
						<?php foreach($outlets as $mediaCode => $outlet){?>
						<div class="column" mediacode="<?php print $mediaCode?>">
							<?php @print number_format($spending[$companyCode][$brandCode][$mediaCode])?>
						</div>
						<?php }?>
					</div>					
				<?php }?>
				<div class="row company-row">
					<div class="column">
						<strong>
							<?php print strtolower($companies[$companyCode])?>
						</strong>
					</div>
					<?php foreach($outlets as $mediaCode => $outlet){?>
					<div class="column" mediacode="<?php print $mediaCode?>">
						<?php
							$companyTotal = 0;
							foreach($spending[$companyCode] as $mediaSpending){
								@$companyTotal += $mediaSpending[$mediaCode];
							}
						?>
						<strong>
							<?php print number_format($companyTotal)?>
						</strong>
					</div>
					<?php }?>
				</div>			
			<?php }?>
		</div>
	<?php } else { ?>
	<div class="report-content" style="width:100%;height:200px;text-align:left;">
		<br/>
		&#160; I did not find any records matching dates <?php print $from?> to <?php print $to?>
	</div>
	<?php }?>
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<script type="text/javascript" src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
	<script>
		$(function() {
			$( ".datepicker" ).datepicker({dateFormat : "yy-mm-dd"});
		});
		function removeColumn(mediaCode){
			$('div[mediaCode="' + mediaCode + '"]').remove();
		}
	</script>	
</body>
</html>
