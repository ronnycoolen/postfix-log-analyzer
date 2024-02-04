#!/usr/bin/php
<?php
$logfile = '/var/log/mail.log';

$lines = file($logfile);

$mails = [];

foreach ($lines as $line) {
	if (preg_match('/: (.*?): from=<(.*?)>,/', $line, $matches)) {
		$mails[$matches[1]] = [
			'from' => $matches[2]
		];
	}
	if (preg_match('/(\d{4}-\d{2}-\d{2}T\d\d:\d\d:\d\d).\d+\+\d+:\d+\s.*: (.*?): to=<(.*?)>,.*status=(.*?) (.*)/', $line, $matches)) {
		if (isset($mails[$matches[2]])) {
			$mails[$matches[2]]['date'] = new DateTime($matches[1]);
			$mails[$matches[2]]['to'] = $matches[3];
			$mails[$matches[2]]['status'] = $matches[4];
			$mails[$matches[2]]['reason'] = $matches[5];
		}
	}
}

$filter = [];

if ($startDate = getenv('STARTDATE')) {
	$filter['startDate'] = new DateTime($startDate);
}
if ($endDate = getenv('ENDDATE')) {
	$filter['endDate'] = new DateTime($endDate);
}

if ($sender = getenv('SENDER')) {
	$filter['sender'] = $sender;
}

$mails = array_filter($mails, function($mail) use ($filter) {
	$check = isset($mail['status']);

	if (isset($filter['startDate'])) {
		$check = $check && $mail['date']->format('U') > $filter['startDate']->format('U');
	}

	if (isset($filter['endDate'])) {
		$check = $check && $mail['date']->format('U') < $filter['endDate']->format('U');
	}

	if (isset($filter['sender'])) {
		$check = $check && stristr($mail['from'], $filter['sender']);
	}

	return $check;
});

$perstatus = [];

foreach ($mails as $mail) {
	if (!isset($perstatus[$mail['status']])) {
		$perstatus[$mail['status']] = [];
	}

	if ($mail['status'] === 'bounced') {
		$perstatus[$mail['status']][] = $mail['to'] . ' ' . $mail['reason'];
	} else {
		$perstatus[$mail['status']][] = $mail['to'];
	}
}

var_dump($perstatus);
