<h2>Instructions</h2>
<p> Please open a ticket <a href="https://github.com/bmlt-enabled/contacts-bmlt/issues" target="_top">https://github.com/bmlt-enabled/contacts-meetings-bmlt/issues</a> with problems, questions or comments.</p>
<div id="contacts_bmlt_accordion">
    <h3 class="help-accordian"><strong>Basic</strong></h3>
    <div>
        <p>[contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot;]</p>
        <strong>Attributes:</strong> root_server, display_type, parent_id, show_description, show_email, show_url_in_name, show_tel_url, show_full_url, show_all_services, show_locations
        <p><strong>Most Shortcode parameters can be combined.</strong></p>
    </div>
    <h3 class="help-accordian"><strong>Shortcode Attributes</strong></h3>
    <div>
        <p>The following shortcode attributes may be used.</p>
        <p><strong>root_server</strong></p>
        <p><strong>display_type</strong></p>
        <p><strong>parent_id</strong></p>
        <p><strong>show_description</strong></p>
        <p><strong>show_email</strong></p>
        <p><strong>show_url_in_name</strong></p>
        <p><strong>show_tel_url</strong></p>
        <p><strong>show_full_url</strong></p>
        <p><strong>show_all_services</strong></p>
        <p><strong>show_locations</strong></p>
        <p>A minimum of root_server attribute is required.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- root_server</strong></h3>
    <div>
        <p><strong>root_server (required)</strong></p>
        <p>The url to your BMLT root server.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- display_type</strong></h3>
    <div>
        <p><strong>display_type</strong></p>
        <p>To change the display type add display_type="table" there are two different types <strong>table</strong>, <strong>block</strong> the default is table</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- parent_id</strong></h3>
    <div>
        <p><strong>parent_id</strong></p>
        <p>This will only display service bodies who has set parent_id.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; parent_id=&quot;22&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_description</strong></h3>
    <div>
        <p><strong>show_description</strong></p>
        <p>This will display the service bodies description underneath the name if set.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_description=&quot;1&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_email</strong></h3>
    <div>
        <p><strong>show_email</strong></p>
        <p>This will display the service bodies contact email underneath the name if set.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_email=&quot;1&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_url_in_name</strong></h3>
    <div>
        <p><strong>show_url_in_name</strong></p>
        <p>This will add a link to the service body name, this is the default action. To remove the url from the service body name add show_url_in_name=&quot;0".</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_url_in_name=&quot;0"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_tel_url</strong></h3>
    <div>
        <p><strong>show_tel_url</strong></p>
        <p>This will add a tel link to the telephone number. Default is to not add it.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_tel_url=&quot;1"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_full_url</strong></h3>
    <div>
        <p><strong>show_full_url</strong></p>
        <p>This will add a separate column or div with the full url displayed. Default is to not add it.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_full_url=&quot;1"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_all_services</strong></h3>
    <div>
        <p><strong>show_all_services</strong></p>
        <p>This will display all service bodies regardless of whether they have their phone or URL field filled out. The default is not to display them.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_all_services=&quot;1"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;- show_locations</strong></h3>
    <div>
        <p><strong>show_locations</strong></p>
        <p>This will display a list of locations below the service body name. Accepted values are location_neighborhood, location_city_subsection, location_municipality, location_sub_province.</p>
        <p>Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_locations=&quot;location_municipality"]</p>
    </div>
</div>
