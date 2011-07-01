<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
  "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<title>bacula-web</title>
<link rel="stylesheet" type="text/css" href="style/default.css">
</head>
<body>
{include file=header.tpl}

  <div id="nav">
    <a href="index.php" title="Back to the dashboard">Dashboard</a> > Jobs list
  </div>

  <div id="main_center">
  
  <!-- Last jobs -->  
  <div class="box">
	<p class="title">Jobs report</p>
	<!-- Filter jobs -->
	<form action="jobs.php" method="post">
	<table border="0">
	  <tr>
	    <td class="info" width="200">
			{$total_jobs} jobs found
		</td>
		<td class="info" colspan="5">
			Jobs / Page
			<select name="jobs_per_page">
			  {foreach from=$jobs_per_page item=nb_jobs}
			    <option value="{$nb_jobs}" {if $smarty.post.jobs_per_page == $nb_jobs}Selected{/if} >{$nb_jobs}
			  {/foreach}
			</select>
		</td>
		<td class="info" width="160">
			Job status
			{html_options name=status values=$job_status options=$job_status selected=$job_status_filter onChange="submit();"}
		</td>
		<!--
		<td class="info" width="120">
			<input type="submit" value="Update" />
		</td>
		-->
	  </tr>
	  <tr>
		<td colspan="8">&nbsp;</td>
	  </tr>
	</table>
	</form>
	
	<table border="0">
	  <tr>
		<td class="tbl_header">Status</td>
		<td class="tbl_header">Job ID</td>
		<td class="tbl_header">BackupJob</td>
		<td class="tbl_header">Start Time</td>
		<td class="tbl_header">End Time</td>
		<td class="tbl_header">Elapsed time</td>
		<td class="tbl_header">Level</td>
		<td class="tbl_header">Bytes</td>
		<td class="tbl_header">Files</td>
		<td class="tbl_header">Pool</td>
	  </tr>
	<!-- <div class="listbox"> -->
	  {foreach from=$last_jobs item=job}
	  <tr>
		<td width="50" class="{$job.Job_classe}">
			<img width="20" src="style/images/{$job.Job_icon}" alt="" title="{$job.jobstatuslong}" />
		</td>
		<td class="{$job.Job_classe}">{$job.jobid}</td>
		<td class="{$job.Job_classe}">
			<a href="backupjob-report.php?backupjob_name={$job.Job_name}">{$job.job_name}</a>
		</td>
		<td class="{$job.Job_classe}">{$job.starttime}</td>
		<td class="{$job.Job_classe}">{$job.endtime}</td>
		<td class="{$job.Job_classe}">{$job.elapsed_time}</td>
		<td class="{$job.Job_classe}">{$job.level}</td>
		<td class="{$job.Job_classe}">{$job.jobbytes}</td>
		<td class="{$job.Job_classe}">{$job.jobfiles}</td>
		<td class="{$job.Job_classe}">{$job.pool_name}</td>
	  </tr>
	  {/foreach}
	</table>
	<!-- </div> --> <!-- end div class=listbox -->
  </div>

</div>

{include file="footer.tpl"}
