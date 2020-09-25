<?php

namespace Poller\Services;

use Poller\Exceptions\SnmpException;
use Poller\Log;
use Poller\Models\SnmpResponse;

class SnmpClient
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function walk(string $oid):SnmpResponse
    {
        //bulkwalk if v2/v3, regular if v1
        if ((int)$this->options['version'] === 1) {
            $cmd = '/usr/bin/snmpwalk '
                . implode(' ', $this->buildSnmpOptions(10))
                . ' '
                . escapeshellarg($this->options['host'])
                . ' '
                . escapeshellarg($oid);
        } else {
            $cmd = '/usr/bin/snmpbulkwalk '
                . implode(' ', $this->buildSnmpOptions())
                . ' '
                . escapeshellarg($this->options['host'])
                . ' '
                . escapeshellarg($oid);
        }

        exec($cmd . ' 2>&1', $output, $returnVar);
        if ($returnVar === 0) {
            return new SnmpResponse($output);
        } else {
            throw new SnmpException("From $cmd: " . implode(', ', $output));
        }
    }

    public function get($oids):SnmpResponse
    {
        if (is_array($oids)) {
            $oidString = implode(' ', array_map(function ($oid) {
                return escapeshellarg($oid);
            }, $oids));
        } else {
            $oidString = $oids;
        }

        $cmd = '/usr/bin/snmpget -Cf '
            . implode(' ', $this->buildSnmpOptions())
            . ' '
            . escapeshellarg($this->options['host'])
            . ' '
            . $oidString;

        exec($cmd . ' 2>&1', $output, $returnVar);
        if ((int)$returnVar === 0) {
            return new SnmpResponse($output);
        } else {
            throw new SnmpException("From $cmd: " . implode(', ', $output));
        }
    }

    private function buildSnmpOptions(int $timeout = 2)
    {
        $options = [
            '-v' . $this->convertVersion(),
            '-r1',
            '-t' . $timeout,
            '-Oe',
            '-On',
            '-Ot',
            '-OU',
            '-O0',
            '-Lo',
            '--hexOutputLength=0',
        ];

        if ($this->options['version'] !== 3) {
            $options[] = '-c' . escapeshellarg($this->options['community']);
        } else {
            $options[] = '-l' . escapeshellarg($this->convertSecLevel());
            $options[] = '-n' . escapeshellarg($this->options['context_name']);
            $options[] = '-a' . escapeshellarg($this->options['auth_mech']);
            $options[] = '-A' . escapeshellarg($this->options['auth_pwd']);
            if ($options['engine_id']) {
                $options[] = '-E' . escapeshellarg($options['engine_id']);
            }
            $options[] = '-x' . escapeshellarg($this->options['priv_mech']);
            $options[] = '-X' . escapeshellarg($this->options['priv_pwd']);
            $options[] = '-u' . escapeshellarg($this->options['user']);

        }

        return $options;
    }

    private function convertSecLevel():string
    {
        switch ($this->options['sec_level']) {
            case 'NO_AUTH_NO_PRIV':
                return 'noAuthNoPriv';
            case 'AUTH_NO_PRIV':
                return 'authNoPriv';
            default:
                return 'authPriv';
        }

    }

    private function convertVersion():string
    {
        switch ($this->options['version']) {
            case 1:
                return 1;
            case 3:
                return 3;
            case 2:
            default:
                return '2c';
        }
    }
}
