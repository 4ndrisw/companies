<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-companies" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('company_number'); ?> #</th>
            <th><?php echo _l('company_list_program'); ?></th>
            <th><?php echo _l('company_list_date'); ?></th>
            <th><?php echo _l('company_list_state'); ?></th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($companies as $company){ ?>
            <tr>
                <td><?php echo '<a href="' . site_url("companies/show/" . $company["id"] . '/' . $company["hash"]) . '">' . format_company_number($company["id"]) . '</a>'; ?></td>
                <td><?php echo $company['name']; ?></td>
                <td><?php echo _d($company['date']); ?></td>
                <td><?php echo format_company_state($company['state']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
