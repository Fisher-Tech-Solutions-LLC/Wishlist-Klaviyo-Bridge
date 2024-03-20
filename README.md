# Simple Plugin to Track "Add to Wishlist" Data On Klaviyo
- Requires you to be using the [Yith Wishlist Plugin](https://wordpress.org/plugins/yith-woocommerce-wishlist/) as well as the [Klaviyo WordPress Integration Plugin](https://wordpress.org/plugins/klaviyo/)
- From your Klaviyo Platform, you will need to [create a private API key](https://help.klaviyo.com/hc/en-us/articles/7423954176283) with full access to the Events Data API
- Insert your API Key in the Settings->Klaviyo Wishlist Bridge options page under the Klaviyo Private API Key field and submit to save the private key to the database

## Data Collected
- ProductID
- ProductName
- ProductURL
- ProductImage
- ProductValue
- ProductCategories
- Time

Customer Email is set to Klaviyo for events matching
