<?php
ob_start();
function wp_paykun_donation_listings_page() {
    ?>

    <div>
        <h1>Paykun Payment Details</h1>
        <table cellpadding="0" cellspacing="0" bgcolor="#ccc" width="99%">
            <tr>
                <td><table cellpadding="10" cellspacing="1" width="100%">
                        <?php
                        global $wpdb;

                        $total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "paykun_donation");

                        $records_per_page = 10;
                        $page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
                        $offset = ( $page * $records_per_page ) - $records_per_page;

                        $donationEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paykun_donation order by date desc limit ".$offset. " , ".$records_per_page);

                        if (count($donationEntries) > 0) { ?>
                            <thead>
                            <tr>
                                <th width="8%" align="left" bgcolor="#FFFFFF">Order Id</th>
                                <th width="8%" align="left" bgcolor="#FFFFFF">Payment Id</th>
                                <th width="8%" align="left" bgcolor="#FFFFFF">Name</th>
                                <th width="10%" align="left" bgcolor="#FFFFFF">Email & Phone</th>
                                <th width="10%" align="left" bgcolor="#FFFFFF">Address</th>
                                <th width="8%" align="left" bgcolor="#FFFFFF">City</th>
                                <th width="8%" align="left" bgcolor="#FFFFFF">State</th>
                                <th width="5%" align="left" bgcolor="#FFFFFF">Country</th>
                                <th width="5%" align="left" bgcolor="#FFFFFF">Zipcode</th>
                                <th width="5%" align="left" bgcolor="#FFFFFF">Donation</th>
                                <th width="7%" align="left" bgcolor="#FFFFFF">Payment Status</th>
                                <th width="8%" align="left" bgcolor="#FFFFFF">Date</th>
                            </tr>
                            <?php foreach ($donationEntries as $row) { ?>
                                <tr>
                                    <td bgcolor="#FFFFFF"><?php echo $row->id ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->payment_id ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->name ?></td>
                                    <td bgcolor="#FFFFFF">
                                        <span class="pk-email">
                                            <?php echo $row->email; ?>
                                        </span>
                                        <br>
                                        <span class="pk-phone">

                                        </span>
                                        <?php echo $row->phone; ?>
                                    </td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->address; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->city; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->state; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->country; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->zip; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->amount; ?></td>
                                    <td bgcolor="#FFFFFF" class="<?php echo getClass($row->payment_status)?>"><?php echo $row->payment_status; ?></td>
                                    <td bgcolor="#FFFFFF"><?php echo $row->date; ?></td>
                                </tr>
                            <?php } ?>
                            </thead>
                        <?php } else { echo "No Record's Found."; } ?>
                    </table></td>
            </tr>
        </table>
        <?php
        $pagination = paginate_links( array(
            'base' => add_query_arg( 'cpage', '%#%' ),
            'format' => '',
            'prev_text' => __('Previous'),
            'next_text' => __('Next'),
            'total' => ceil($total / $records_per_page),
            'current' => $page
        ));
        ?>
        <div class="donation-pagination">
            <?php echo $pagination; ?>
        </div>
    </div>
    <style type="text/css">
        .pk-failed-payment, .pk-success-payment {
            border-bottom-left-radius: 13%;
            border-top-left-radius: 13%;
        }
        .pk-failed-payment {
            border-left: 5px solid red;
        }
        .pk-success-payment {
            border-left: 5px solid darkgreen;
        }
        /*.pk-email::before{
            content: "\f466";
        }*/
    </style>
<?php } ?>
<?php
    function getClass($payment_status) {
        if("Complete Payment" == $payment_status) {
            return "pk-success-payment";
        }
        return "pk-failed-payment";
    }
?>
