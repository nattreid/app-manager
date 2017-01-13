<?php

namespace NAttreid\AppManager\Helpers;

use Nette\SmartObject;
use Nette\Utils\Strings;
use stdClass as Obj;

/**
 * Informace o serveru
 *
 * @property-read string $phpInfo Vrati vypis PHP info
 * @property-read string $ip Vrati IP adresu serveru
 * @property-read string $load Vrati vytizeni serveru
 * @property-read Obj $system Vrati informace o systemu
 * @property-read Obj $hardware Vrati informace o hardware
 * @property-read Obj $memory Vrati informace o pameti
 * @property-read Obj[] $fileSystem Vrati informace o souborovem system
 * @property-read Obj[]|null $network Vrati informace o siti
 *
 *
 * @author Attreid <attreid@gmail.com>
 */
class Info
{
	use SmartObject;

	/**
	 * Vrati vypis PHP info
	 * @return array
	 */
	protected function getPhpInfo()
	{
		ob_start();
		phpinfo();
		$result = ob_get_contents();
		ob_end_clean();

		$result = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $result);
		$result = preg_replace('/,\s*/', ', ', $result);
		return $result;
	}

	/**
	 * Precteni souboru do retezce
	 * @param string $file
	 * @param string $explode
	 * @return string
	 */
	private function readFile($file, $explode = null)
	{
		$result = @file_get_contents($file);
		if (!empty($result)) {
			if ($explode !== null) {
				return explode($explode, $result);
			}
			return $result;
		}
		return null;
	}

	/**
	 * Precteni prikazu do retezce
	 * @param string $command
	 * @param string $explode
	 * @return string|array
	 */
	private function readCommand($command, $explode = null)
	{
		@exec($command, $result);
		if (!empty($result)) {
			$result = implode("\n", $result);
			if ($explode !== null) {
				return explode($explode, $result);
			}
			return $result;
		}
		return null;
	}

	/**
	 * Vrati informace o systemu
	 * @return Obj
	 */
	protected function getSystem()
	{
		$system = new Obj;

		$system->hostname = $this->readFile('/etc/hostname');
		$system->ip = $this->getIp();
		$system->distribution = Strings::replace($this->readCommand('lsb_release -d'), '/Description:\s/');
		$system->kernel = $this->readCommand('uname -r') . ' ' . $this->readCommand('uname -i');

		$uptime = $this->readFile('/proc/uptime', ' ');
		if ($uptime) {
			$system->uptime = new Obj;
			$system->uptime->days = (int)gmdate("d", $uptime[0]) - 1;
			$system->uptime->hours = (int)gmdate("H", $uptime[0]);
			$system->uptime->minutes = (int)gmdate("i", $uptime[0]);
		}

		$users = $this->readCommand('users', ' ');
		if ($users) {
			$system->users = new Obj;
			$system->users->list = $users;
			$system->users->count = count($system->users->list);
		}

		$load = $this->getLoad();
		if ($load) {
			$system->load = $load;
		}

		$system->processes = new Obj;
		$system->processes->total = $this->readCommand('ps axo state | wc -l');
		$system->processes->running = $this->readCommand('ps axo state | grep "R" | wc -l');
		$system->processes->sleeping = $system->processes->total - $system->processes->running;

		return $system;
	}

	/**
	 * Vrati IP adresu serveru
	 * @return string
	 */
	protected function getIp()
	{
		return filter_input(INPUT_SERVER, 'SERVER_ADDR');
	}

	/**
	 * Vrati vytizeni serveru
	 * @return string
	 */
	protected function getLoad()
	{
		$load = $this->readFile('/proc/loadavg', ' ');
		if ($load) {
			unset($load[3], $load[4]);
			return implode(' ', $load);
		}
		return null;
	}

	/**
	 * Vrati informace o hardware
	 * @return Obj
	 */
	protected function getHardware()
	{
		$hardware = new Obj;

		$product = $this->readFile('/sys/devices/virtual/dmi/id/product_name');
		$board = $this->readFile('/sys/devices/virtual/dmi/id/board_name');
		$bios = $this->readFile('/sys/devices/virtual/dmi/id/bios_version');
		$biosDate = $this->readFile('/sys/devices/virtual/dmi/id/bios_date');

		$server = $product
			. (!empty($board) ? '/' . $board : null)
			. (!empty($bios) ? ', BIOS ' . $bios : null)
			. (!empty($biosDate) ? ' ' . $biosDate : null);
		if ($server) {
			$hardware->server = $server;
		}

		if (!empty($cpu = $this->getCpu())) {
			$hardware->cpu = $cpu;
		}

		$hardware->scsi = $this->getScsi();

		return $hardware;
	}

	/**
	 * Vrati informace o cpu
	 * @return Obj[]
	 */
	private function getCpu()
	{
		$result = [];
		$cpuInfo = $this->readFile('/proc/cpuinfo');
		if (empty($cpuInfo)) {
			return null;
		}

		$processors = preg_split('/\s?\n\s?\n/', trim($cpuInfo));
		$procname = null;
		foreach ($processors as $processor) {
			$cpu = new Obj;
			$proc = null;
			$arch = null;
			$details = preg_split("/\n/", $processor, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($details as $detail) {
				$arrBuff = preg_split('/\s*:\s*/', trim($detail));
				if (count($arrBuff) == 2) {
					switch (strtolower($arrBuff[0])) {
						case 'processor':
							$proc = trim($arrBuff[1]);
							if (is_numeric($proc)) {
								if (strlen($procname) > 0) {
									$cpu->model = $procname;
								}
							} else {
								$procname = $proc;
								$cpu->model = $procname;
							}
							break;
						case 'model name':
						case 'cpu model':
						case 'cpu type':
						case 'cpu':
							$cpu->model = $arrBuff[1];
							break;
						case 'cpu mhz':
						case 'clock':
							if ($arrBuff[1] > 0) { //openSUSE fix
								$cpu->speed = $arrBuff[1] * 1000 * 1000;
							}
							break;
						case 'cycle frequency [hz]':
							$cpu->speed = $arrBuff[1] * 1000 * 1000;
							break;
						case 'cpu0clktck':
							$cpu->speed = hexdec($arrBuff[1] * 1000 * 1000);
							break;
						case 'l2 cache':
						case 'cache size':
							$cpu->cache = preg_replace("/[a-zA-Z]/", "", $arrBuff[1]) * 1024;
							break;
						case 'initial bogomips':
						case 'bogomips':
						case 'cpu0bogo':
							$cpu->bogomips = $arrBuff[1];
							break;
						case 'i size':
						case 'd size':
							if ($cpu->cache === null) {
								$cpu->cache = $arrBuff[1] * 1024;
							} else {
								$cpu->cache = $cpu->cache + ($arrBuff[1] * 1024);
							}
							break;
						case 'cpu architecture':
							$arch = trim($arrBuff[1]);
							break;
					}
				}
			}
			// sparc64 specific code follows
			// This adds the ability to display the cache that a CPU has
			// Originally made by Sven Blumenstein <bazik@gentoo.org> in 2004
			// Modified by Tom Weustink <freshy98@gmx.net> in 2004
			$sparclist = array('SUNW,UltraSPARC@0,0', 'SUNW,UltraSPARC-II@0,0', 'SUNW,UltraSPARC@1c,0', 'SUNW,UltraSPARC-IIi@1c,0', 'SUNW,UltraSPARC-II@1c,0', 'SUNW,UltraSPARC-IIe@0,0');
			foreach ($sparclist as $name) {
				$str = $this->readFile('/proc/openprom/' . $name . '/ecache-size');
				if ($str) {
					$cpu->cache = base_convert($str, 16, 10);
				}
			}
			// sparc64 specific code ends
			// XScale detection code
			if (($arch === "5TE") && ($cpu->bogomips != null)) {
				$cpu->speed = $cpu->bogomips; //BogoMIPS are not BogoMIPS on this CPU, it's the speed
				$cpu->bogomips = null; // no BogoMIPS available, unset previously set BogoMIPS
			}

			if ($proc != null) {
				if (!is_numeric($proc)) {
					$proc = 0;
				}
				// variable speed processors specific code follows
				if ($str = $this->readFile('/sys/devices/system/cpu/cpu' . $proc . '/cpufreq/cpuinfo_cur_freq')) {
					$cpu->speed = $str * 1000;
				} elseif ($str = $this->readFile('/sys/devices/system/cpu/cpu' . $proc . '/cpufreq/scaling_cur_freq')) {
					$cpu->speed = $str * 1000;
				}
				if ($str = $this->readFile('/sys/devices/system/cpu/cpu' . $proc . '/cpufreq/cpuinfo_max_freq')) {
					$cpu->speedMax = $str * 1000;
				}
				if ($str = $this->readFile('/sys/devices/system/cpu/cpu' . $proc . '/cpufreq/cpuinfo_min_freq')) {
					$cpu->speedMin = $str * 1000;
				}
				if ($str = $this->readFile('/proc/acpi/thermal_zone/THRM/temperature')) {
					$cpu->temperature = substr($str, 25, 2);
				}
			}

			$cpu->usage = null;
			$result[] = $cpu;
		}


		// CPU usage
		$command = "grep 'cpu' /proc/stat";
		$cpuStats1 = $this->readCommand($command, "\n");
		sleep(1);
		$cpuStats2 = $this->readCommand($command, "\n");

		if (count($processors) == 1) {

		}
		$counter = 0;
		for ($i = 0; $i < count($cpuStats1); $i++) {
			if (preg_match('/cpu[0-9]{1,2}/', $cpuStats1[$i]) > 0) {
				$stats1 = explode(' ', $cpuStats1[$i]);
				$stats2 = explode(' ', $cpuStats2[$i]);

				$prevIdle = $stats1[4] + $stats1[5];
				$idle = $stats2[4] + $stats2[5];
				$prevNonIdle = $stats1[1] + $stats1[2] + $stats1[3] + $stats1[6] + $stats1[7] + $stats1[8];
				$nonIdle = $stats2[1] + $stats2[2] + $stats2[3] + $stats2[6] + $stats2[7] + $stats2[8];
				$prevTotal = $prevIdle + $prevNonIdle;
				$total = $idle + $nonIdle;

				$result[$counter++]->usage = (float)(($total - $prevTotal) - ($idle - $prevIdle)) / ($total - $prevTotal) * 100;
			}
		}
		return $result;
	}

	/**
	 * Vrati informace o scsi
	 * @return Obj[]
	 */
	private function getScsi()
	{
		$get_type = false;
		$device = null;
		$scsi = [];

		$bufe = preg_split("/\n/", $this->readFile('/proc/scsi/scsi'), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($bufe as $buf) {
			if (preg_match('/Vendor: (.*) Model: (.*) Rev: (.*)/i', $buf, $devices)) {
				$get_type = true;
				$device = $devices;
				continue;
			}
			if ($get_type) {
				preg_match('/Type:\s+(\S+)/i', $buf, $dev_type);
				$dev = new Obj;
				$dev->name = trim($device[1]) . ' ' . trim($device[2]);
				$dev->type = trim($dev_type[1]);
				$scsi[] = $dev;
				$get_type = false;
			}
		}
		return empty($scsi) ? null : $scsi;
	}

	/**
	 * Vrati informace o pameti
	 * @return Obj
	 */
	protected function getMemory()
	{
		$memory = new Obj;
		$bufer = preg_split("/\n/", $this->readFile('/proc/meminfo'), -1, PREG_SPLIT_NO_EMPTY);

		if (empty($bufer)) {
			return null;
		}

		foreach ($bufer as $buf) {
			if (preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
				$memory->total = $ar_buf[1] * 1024;
			} elseif (preg_match('/^MemFree:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
				$memory->free = $ar_buf[1] * 1024;
			} elseif (preg_match('/^Cached:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
				$memory->cache = $ar_buf[1] * 1024;
			} elseif (preg_match('/^Buffers:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
				$memory->buffer = $ar_buf[1] * 1024;
			}
		}
		$memory->used = $memory->total - $memory->free;

		if ($memory->cache !== null && $memory->buffer !== null) {
			$memory->application = $memory->used - $memory->cache - $memory->buffer;
		}

		$memory->swap = [];
		$swaps = preg_split("/\n/", $this->readFile('/proc/swaps'), -1, PREG_SPLIT_NO_EMPTY);
		unset($swaps[0]);
		foreach ($swaps as $swap) {
			$ar_buf = preg_split('/\s+/', $swap, PREG_BAD_UTF8_OFFSET_ERROR);
			$swap = new Obj;
			$swap->mount = $ar_buf[0];
			$swap->name = 'SWAP';
			$swap->total = $ar_buf[2] * 1024;
			$swap->used = $ar_buf[3] * 1024;
			$swap->free = $swap->total - $swap->used;
			$memory->swap[] = $swap;
		}
		return empty($memory->used) ? null : $memory;
	}

	/**
	 * Vrati informace o souborovem system
	 * @return Obj[]
	 */
	protected function getFileSystem()
	{
		$fileSystem = [];

		$bufe = preg_split("/\n/", $this->readCommand('df -T'), -1, PREG_SPLIT_NO_EMPTY);
		unset($bufe[0]);
		foreach ($bufe as $buf) {
			$data = preg_split('/\s+/', $buf);
			$mounted = new Obj;

			$mounted->partition = $data[0];
			$mounted->type = $data[1];
			$mounted->size = $data[2] * 1024;
			$mounted->used = $data[3] * 1024;
			$mounted->free = $data[4] * 1024;
			$mounted->usage = $data[5];
			$mounted->mountPoint = $data[6];

			$fileSystem[] = $mounted;
		}

		return $fileSystem;
	}

	/**
	 * Vrati informace o siti
	 * @return Obj[]|null
	 */
	protected function getNetwork()
	{
		$network = [];

		$bufe = preg_split("/\n/", $this->readFile('/proc/net/dev'), -1, PREG_SPLIT_NO_EMPTY);
		unset($bufe[0], $bufe[1]);
		foreach ($bufe as $buf) {
			list($dev_name, $stats_list) = preg_split('/:/', $buf, 2);
			$stats = preg_split('/\s+/', trim($stats_list));

			$dev = new Obj;
			$dev->name = trim($dev_name);
			$dev->recieve = $stats[0];
			$dev->sent = $stats[8];
			$dev->error = $stats[2] + $stats[10];
			$dev->drop = $stats[3] + $stats[11];

			$ipBuff = preg_split("/\n/", $this->readCommand('ip addr show ' . $dev->name), -1, PREG_SPLIT_NO_EMPTY);
			foreach ($ipBuff as $line) {
				if (preg_match('/^\s*link\/ether\s+(.*)\s+brd.*/i', $line, $ar_buf)) {
					$dev->mac = $ar_buf[1];
				} elseif (preg_match('/^\s*inet6\s+(.*)\/.*/i', $line, $ar_buf)) {
					$dev->ip6 = $ar_buf[1];
				} elseif (preg_match('/^\s*inet\s+(.*)\/.*/i', $line, $ar_buf)) {
					$dev->ip = $ar_buf[1];
				}
			}
			$network[] = $dev;
		}
		return empty($network) ? null : $network;
	}

}