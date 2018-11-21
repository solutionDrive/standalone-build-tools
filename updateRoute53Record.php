<?php

define('AWS_ROUTE53_TTL', 60);

# First parse command line arguments
if ($argc < 3) {
    file_put_contents(
        'php://stderr',
        "Usage: {$argv[0]} <dns name of zone> <dns record name to set> [<optional ip address>]" .
        PHP_EOL . PHP_EOL .
        "Example: AWS_ROUTE53_WAIT=1 {$argv[0]} cloud.solutiondrive.de direct.machine.cloud.solutiondrive.de" .
        PHP_EOL . PHP_EOL .
        'If you do not specify an ip address, the ip address of the current ec2 machine will be guessed.' .
        PHP_EOL .
        'If you set the environment variable AWS_ROUTE53_WAIT=1 ' .
        'the script will not terminate before the record update is propagated.'
    );
    exit(2);
} elseif ($argc < 4) {
    $ip = findIpFromEc2();
} elseif ($argc === 4) {
    $ip = $argv[3];
}

$zoneDnsName = $argv[1];
$recordDnsName = $argv[2];
$waitForRequestFinished = getenv('AWS_ROUTE53_WAIT');

exec(
    'aws route53 list-hosted-zones-by-name --dns-name ' . $zoneDnsName,
    $listZonesOutputArray,
    $listZonesExitCode
);

if (0 !== $listZonesExitCode) {
    file_put_contents(
        'php://stderr',
        'Could not get hosted zones. Error from AWS CLI:' . PHP_EOL . implode("\n", $listZonesOutputArray)
    );
    exit(1);
}

$listJson = json_decode(implode('', $listZonesOutputArray));
foreach ($listJson->HostedZones as $z) {
    if (in_array($z->Name, [$zoneDnsName, $zoneDnsName.'.'])) {
        $zoneId = $z->Id;

        $changeRecordOutput = updateRecord($zoneId, $recordDnsName, $ip);

        if ($waitForRequestFinished) {
            waitForChangeRequestFinished($changeRecordOutput);
        }

        // Everything went fine. Now close friendly.
        exit(0);
    }
}

// No zone was found matching the given dns name.
file_put_contents('php://stderr', 'Given hosted zone not found by dns name. Abort.');
exit(1);


function findIpFromEc2()
{
    return trim(file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4'));
}

function updateRecord(string $zoneId, string $recordDnsName, string $ip)
{
    $q = trim('{
            "Comment": "update direct record for ' . $recordDnsName . '",
            "Changes": [
                {
                    "Action": "UPSERT",
                    "ResourceRecordSet": {
                        "Name": "' . $recordDnsName . '",
                        "Type": "A",
                        "TTL": ' . AWS_ROUTE53_TTL . ',
                        "ResourceRecords": [{ "Value": "' . $ip . '"}]
                    }
                }
            ]
        }');

    exec(
        'aws route53 change-resource-record-sets --hosted-zone-id ' . $zoneId . ' --change-batch \'' . $q . '\'',
        $changeRecordOutputArray,
        $changeRecordExitCode
    );

    if (0 !== $changeRecordExitCode) {
        file_put_contents(
            'php://stderr',
            'Could not update record. Error from AWS CLI:' . PHP_EOL . implode("\n", $changeRecordOutputArray)
        );
        exit(1);
    }

    return implode('', $changeRecordOutputArray);
}

function waitForChangeRequestFinished(string $changeRecordOutput)
{
    $changeRecordOutputJson = json_decode($changeRecordOutput);
    $id = $changeRecordOutputJson->ChangeInfo->Id;
    exec(
        'aws route53 wait resource-record-sets-changed --id ' . $id,
        $waitOutputArray,
        $waitExitCode
    );

    if (0 !== $waitExitCode) {
        file_put_contents(
            'php://stderr',
            'Waiting for changed record failed. Error from AWS CLI:' . PHP_EOL . implode("\n", $waitOutputArray)
        );
        exit(1);
    }
}
