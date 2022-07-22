<h2>Library interface</h2>

<p>
The <code>scgms</code> library is a central library for creating and managing entities. It exports the following set of functions:
<ul>
    <li><code>create_filter</code> - creates a filter using given GUID and a pointer to next filter</li>
	<li><code>create_metric</code> - creates a metric using given GUID and metric parameters</li>
	<li><code>create_signal</code> - creates a signal using given GUID</li>
	<li><code>create_approximator</code> - creates a signal approximator with given GUID</li>
    <li><code>create_discrete_model</code> - creates a discrete model using given GUID</li>
	<li><code>get_filter_descriptors</code> - retrieves a range (begin, end) of available loaded filter descriptors</li>
	<li><code>get_metric_descriptors</code> - retrieves a range (begin, end) of available loaded metric descriptors</li>
	<li><code>get_model_descriptors</code> - retrieves a range (begin, end) of available loaded model (discrete and signal model) descriptors</li>
	<li><code>get_solver_descriptors</code> - retrieves a range (begin, end) of available loaded solver descriptors</li>
	<li><code>get_approx_descriptors</code> - retrieves a range (begin, end) of available loaded approximator descriptors</li>
	<li><code>get_signal_descriptors</code> - retrieves a range (begin, end) of available loaded signal descriptors</li>
	<li><code>add_filters</code> <i>(deprecated)</i> - injects a filter entity from external code</li>
	<li><code>create_device_event</code> - allocates a device event from a limited size pool</li>
	<li><code>create_persistent_filter_chain_configuration</code> - creates a configuration container for simulation execution</li>
	<li><code>execute_filter_configuration</code> - executes a filter configuration (runs a simulation); this blocks, while the simulation runs</li>
	<li><code>create_filter_parameter</code> - creates a single filter parameter container</li>
	<li><code>create_filter_configuration_link</code> - creates a single filter parameter configuration link</li>
    <li><code>solve_generic</code> - runs a solver with given GUID on given setup</li>
	<li><code>optimize_parameters</code> - optimize parameters of a single filter in given filter chain configuration</li>
	<li><code>optimize_multiple_parameters</code> - optimize parameters of multiple filter in given filter chain configuration</li>
	<li><code>Execute_SCGMS_Configuration</code> - <i>"simple" interface</i> - executes given configuration, returns a simulation handle</li>
	<li><code>Inject_SCGMS_Event</code> - <i>"simple" interface</i> - injects a new device event into a running simulation</li>
	<li><code>Shutdown_SCGMS</code> - <i>"simple" interface</i> - shuts down the simulation, optionally waits for termination</li>
</ul>
Under usual circumstances, the programmer usually takes advantage of supplied C++ SDK, which resolves, encapsulates and calls all exported functions in a properly managed way, often using
higher-level C++ primitives.
</p>

<p>
Every interface function or method returns a status code - a <code>HRESULT</code>. The conventions were adapted from WinAPI standard, including concrete values. All used values
are either taken from appropriate header file on MS Windows, or is additionally defined in SDK file <code>hresult.h</code>. Most commonly used <code>HRESULT</code> values:
<ul>
    <li><code>S_OK</code> - success, the call succeeded without an error and/or warning</li>
    <li><code>S_FALSE</code> - success (might be partial), but the function does not return any result (may be soft failure, or any other non-standard non-errorneous scenario)</li>
    <li><code>E_FAIL</code> - generic failure; this return code is often accompanied with additional error output (using some kind of log container, device event, ...)</li>
    <li><code>E_NOTIMPL</code> - not implemented; e.g. returned as a result of factory function, that is not able to construct a requested entity</li>
    <li><code>E_ABORT</code> - fatal error, should abort the whole simulation, if returned during one</li>
    <li><code>E_UNEXPECTED</code> - the call to this function/method was not expected at this time</li>
    <li><code>E_NOINTERFACE</code> - the entity does not support given interface</li>
    <li><code>E_INVALIDARG</code> - invalid argument supplied (not within valid domain)</li>
    <li><code>E_OUTOFMEMORY</code> - unable to complete the request due to the lack of resources (the system is low on memory, or the pre-allocated pool was depleted)</li>
    <li><code>E_ILLEGAL_STATE_CHANGE</code> - attempt to change the state of an entity, which was invalid at the time</li>
    <li>and more...</li>
</ul>
</p>

<p>
    <!-- get_*_descriptors, ranges -->
    A SmartCGMS-compliant library usually exports a function, that resolves descriptors of all implemented entites within the library (e.g. <code>do_get_filter_descriptors</code>).
    Such a function retains two parameters, which are double-indirect and is expected to be filled with valid range of pointers to descriptor structures. For example, take the
    <code>do_get_filter_descriptors</code> function:
</p>
<code><pre>
scgms::TFilter_Descriptor my_descriptor = ...;

extern "C" HRESULT do_get_filter_descriptors(scgms::TFilter_Descriptor** begin, scgms::TFilter_Descriptor** end)
{
    *begin = &my_descriptor;
    *end = &my_descriptor + 1;

    return S_OK;
}
</pre></code>
<p>
It is worth noting, that the range definition follows the usual contract, i.e.; the <code>begin</code> pointer points to the first valid element and the <code>end</code> pointer
points to one element after the last valid element. It is not included in the range and serves as an upper boundary only.
</p>

<p>
    A SmartCGMS-compliant library also usually exports a function, that creates the requested entity based on given identifier and other parameters (e.g. <code>do_create_filter</code>). The usual contract of such
    function includes the entity GUID and a double indirect pointer, which is expected to be filled with a pointer to newly created entity. For example, take the <code>do_create_filter</code> function:
</p>

<code><pre>
extern "C" HRESULT IfaceCalling do_create_filter(const GUID *id, scgms::IFilter *output, scgms::IFilter **filter)
{
    if (*id == my_filter_guid)
    {
        *filter = Create_My_Filter(output);
        return (*filter != nullptr) ? S_OK : E_OUTOFMEMORY;
    }

    return E_NOTIMPL;
}
</pre></code>

<p>
Both the <code>do_get_*_descriptors</code>, <code>do_create_*</code> functions, descriptors and relevant conventions are further described in respective entity documentation pages.
</p>
