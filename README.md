# Wordpress_v4.x_Donation
This Wordpress 4.x Paykun Donation version enable merchant to get donation from the different donors. No need to install Woocommerce.

# How To Generate Access token and API Secret :
You can generate Or Regenerate Access token and API Secret from login into your paykun admin panel, Then Go To : Settings -> Security -> API Keys. There you will find the generate button if you have not generated api key before.

If you have generated api key before then you will see the date of the api key generate, since you will not be able to retrieve the old api key (For security reasons) we have provided the re-generate option, so you can re-generate api key in case you have lost the old one.

Note : Once you re-generate api key your old api key will stop working immediately. So be cautious while using this option.

# Prerequisite
    Merchant Id (Please read 'How To Generate Access token and API Secret :')
    Access Token (Please read 'How To Generate Access token and API Secret :')
    Encryption Key (Please read 'How To Generate Access token and API Secret :')
# Installation

  1. Download the zip and extract it to the some temporary location
  2. Copy the folder named 'paykun-donate' from the extracted zip into the directory location /wp-content/plugins/
  3. Activate the plugin through the left side 'Plugins' menu in WordPress.
  4. Now you will see new menu in your Wordpress admin called 'Paykun Donation' 
  6. Click on Paykun Donation > Settings
  7. Enter all the required details provided by paykun.
  8. Now you can see Paykun in your payment option.
  9. Save the below configuration.
      * Merchant Id                     - Staging/Production Merchant Id provided by Paykun
      * Access Token                    - Staging/Production Access Token provided by Paykun
      * Encryption Key                  - Staging/Production Encryption Key provided by Paykun
      * Default Amount                  - Enable check box
      * Amount editable by user?        - Paykun
      * Default Payment Button caption  - Default
      * Log (yes/no)                    - For trouble shooting

  10. Your Wordpress Donation plug-in is now installed. You can accept payment through Paykun.

# In case of any query, please contact to Paykun.
