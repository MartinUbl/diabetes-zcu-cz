<?php
function getRatTime($unix)
{
    $diffFrom1970To1900 = 2209161600.0;
    $MSecsPerDay = 24.0*60.0*60.0*1000.0;
    $InvMSecsPerDay = 1.0 / (double)$MSecsPerDay;

    $diff = $unix + (double)$diffFrom1970To1900;
	return 1000.0 * $diff * $InvMSecsPerDay;
}

$curUnixTime = time();
$curTimeStr = date("H:i:s j. n. Y", $curUnixTime);
$curRatTime = getRatTime($curUnixTime);
?>

<h2>Time and time segments</h2>

<p>
There are two major "kinds" of time in SmartCGMS architecture - a <i>device</i> time and a <i>logical</i> time.
</p>

<h3>Device time</h3>

<p>
<i>Device time</i> is a timestamp representing real, wall-clock time. This timestamp is represented in a measure we call a <i>rat time</i>. The time is encoded as the number of
days since January 0, 1900 00:00 UTC, see <a href="http://en.wikipedia.org/wiki/January_0">http://en.wikipedia.org/wiki/January_0</a>. Integral part stores the number of days,
fractional part stores the time within the day. It could have been any fixed dates, but this one is compatible with FreePascal, Delphi and Microsoft Products such as Excel, Access
and COM's variant in general.
</p>

<p>For example, 01 Jan 1900 00:00 would be 1.0 and 01 Jan 1900 24:00 (02 Jan 1900 00:00) would be 2.0, and so on.</p>

<p>The SDK contains <code>rattime.h</code> and <code>rattime.cpp</code> files, which offers functions to convert UNIX timestamps to rat time and vice versa.</p>

<p>
Current date and time: <?php echo $curTimeStr; ?><br />
Current unix timestamp: <?php echo $curUnixTime; ?><br />
Current rat time: <?php echo $curRatTime; ?><br />
</p>

<h3>Logical time</h3>

<p>
Logical time is an increasing sequence of numbers, assigned to device events as they are created. This helps us mark the logical sequence of device events, and sort them by the
time of their creation.
</p>

<p>
Preferably just one global source of logical time should be present per single SmartCGMS-based simulation. However, since the SmartCGMS is partially ready for distributed applications,
more logical time sources (and more logical time sequences) may be present. The synchronization between more logical clocks is currently unsupported.
</p>


<h3>Time segments</h3>

<p>
Time segments are a set of measured values, bounded by two markers - time segment start and end (device events). This functionality was introduced mainly due to the fact, that more
parallel measurements may occur at one time, and so is their processing. In order to process all values from all running measurements independently, the concept of time segments was
introduced.
</p>

<p>
A time segment is identified by its simulation-unique number, which may be persistent based on the configuration. If more time segments with the same segment identifier exist,
the conflict must be resolved outside of SmartCGMS context (e.g. by an external script modifying the database, etc.), as the architecture itself is unable to resolve it.
</p>

<p>
Every time segment must start with the device event marked with <code>Time_Segment_Start</code> code. This device event contains a device time, that is less or equal to a value of
the first level of such segment. Then, a set of levels, parameters and other data relevant to this segment may be sent through the filter chain. When the time segment reaches its end
(e.g.; its last level from database was sent), a device event marked with <code>Time_Segment_End</code> code must occur. This marker allows for proper resource deallocation, and 
also marks the end of the time segment visually in drawings.
</p>

<p>
Any device event containing the time segment identifier sent outside of device time boundaries (marked with <code>Time_Segment_Start</code> and <code>Time_Segment_End</code> events)
is ignored and will be discarded. It is also worth noting, that any relevant device event should also have its logical time within corresponding boundaries (logical times of given markers).
</p>
