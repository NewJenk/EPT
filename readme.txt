=== Encrypted Post Type ===
Contributors: NewJenk
Tags: encryption, privacy, security
Requires at least: 5.5.0
Requires PHP: 7.2
Tested up to: 6.0.2
Stable tag: 1.0.0
License: GPLv3 and others
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Encrypted Post Type provides a custom post type where the content of each post is encrypted.

== Description ==

When you're doing things that are confidential, or private, or personal, then they should remain so.

Encrypted Post Type adds an encrypted <a href="https://wordpress.org/support/article/post-types/#custom-post-types">post type</a> where the content of posts is encrypted using <a href="https://www.openssl.org/">OpenSSL</a>. Use it to write notes, keep a diary, draft letters, plan your next career move, even project manage - basically anything important that you want to keep private, Encrypted Post Type is the place to put it.

Coming complete with an advanced but simple tagging system you can easily organise your posts to create a powerful tool that works just the way you need it to, and can replace other tools like Roam, Workflowy, OneNote, Evernote, and more.

### Features of this plugin

* Easily tag your posts to organise them and build relationships between things you're working on. Never used tags before? Here's a handy <a href="https://encryptedposttype.com/kb/beginners-guide-to-tags/">guide on using tags</a>.
* Works with all core Gutenberg blocks and should work with most custom blocks that aren't doing anything too funky with the markup.
* Collaboration out of the box: multiple users can view and edit posts, with encryption/decryption happening seamlessly in the background (the <a href="https://encryptedposttype.com/pro/">Pro version</a> allows individual posts, viewable only to the author).
* Choose a name for the post type. By default it's set to 'Notes' but you can name it anything you like, and even set an icon in the sidebar! <a href="https://encryptedposttype.com/kb/naming-your-post-type/">Read more about naming the post type here</a>.
* It's been tested with content over 20,000 words in length and worked an absolute champ!
* Posts display in order of most recently edited on the 'All Posts' screen; this is a great way to quickly see what you're working on right now. You can re-order by created date, title, and you can change last updated to ascending (oldest first).
* Revisions work! Content is decrypted on the fly so you can see the differences between versions.
* The free version has 1 way of storing the encryption keys, but the <a href="https://encryptedposttype.com/pro/">Pro version</a> beefs up security significantly by introducing an innovation called <a href="https://encryptedposttype.com/kb/rest-key-management-rkm/">Rest Key Management (RKM)</a>.
* You can easily add links via the link pop-up of the paragraph and heading block to other posts, and when you click on one of the links you'll go straight to the post!
* The block editor (Gutenberg) also includes word, character, paragraph, and heading counts, so you can easily keep track on the progress of what you're writing all within the block editor without having to rely on additional tools. <a href="https://github.com/WordPress/gutenberg/pull/41611">Reading length</a> will be added in a future version of the block editor, which will come in really handy for drafting documents.

Want a feature added? You can <a href="https://us1.onform.net/encryptedposttype/request-a-feature/">request a new feature here</a>.

### How does it work?

The block editor (Gutenberg) saves data in post_content as html markup - it's this that is encrypted.

When the plugin is installed and activated a key is randomly generated that is saved in the options table of your site. This key is **not** used to encrypt content of posts - we'll come back to it in a second. A directory is also created in the uploads directory that is used to store the encryption keys - the keys in this directory are used to encrypt data, but before they are saved in the directory they are encrypted with the key that was saved in the options table (with the Pro version the keys are saved on a different site for added security). So, the encryption keys are themselves encrypted.

When you create a new post the encryption key for that post is saved in the directory mentioned above (but remember, it's encrypted before being saved) along with something called an Initialisation Vector (IV), which ensures the encrypted output (ciphertext) is unique.

When you save your post the key that was saved in the directory when the post was initially created is first decrypted using [a] the key saved in the options table, and [b] the Initialisation Vector (IV) that was saved alongside the key; the decrypted key is then used to encrypt the content and an IV is also saved alongside the post. The IV is updated each time the post is saved to ensure the encrypted output (ciphertext) is unique.

Encryption is done using <a href="https://en.wikipedia.org/wiki/Advanced_Encryption_Standard">aes128</a> and the <a href="https://www.php.net/manual/en/book.openssl.php">OpenSSL library</a>.

#### Important considerations

* Media that you upload to your site is not encrypted. If you want this feature <a href="https://us1.onform.net/encryptedposttype/request-a-feature/">request it here</a>.
* If you delete your encryption keys and you don't have a backup there's no way of getting your data back. It will be gone for good.
* Reusable blocks are not encrypted. If you want this feature <a href="https://us1.onform.net/encryptedposttype/request-a-feature/">request it here</a>.
* Each post has its own encryption key that will be saved in a file (or via <a href="https://encryptedposttype.com/kb/rest-key-management-rkm/">RKM</a>). These files are very small (approx 255 bytes), which means 3,900 will take up approximately 1MB, and 3,900,000 will take up approximately 1GB of server space. It's safe to say you'll have to create lots and lots and lots of posts before space becomes an issue.
* Encryption should be part of a broader security strategy. There are a few simple things you can do to help protect your data in addition to using Encrypted Post Type: [1] use a strong password, [2] use 2-factor authentication, [3] minimise the number of plugins you use, and only use plugins from reputable sources, [4] keep WordPress up-to-date, including your theme/s and plugins.
* Encryption happens server-side. End-to-end encryption was considered but there are limitations to end-to-end encryption that make it impractical in many applications. There are plenty of legitimate use cases where server-side encryption makes more sense. For example, there are several potential features in the pipe-line like reminders and mentions that would be very very difficult to pull off with end-to-end encryption.
* Encryption is complex, and Encrypted Post Type aims to bring encryption to WordPress in a way that is accessible to all. As with all software, there may be bugs present. The plugin is open source and if you spot a bug please feel free to contribute over on Github here: <a href="https://github.com/NewJenk/EPT/">github.com/NewJenk/EPT</a>, pull requests are welcome.

### Why WordPress, the block editor (Gutenberg), and Encrypted Post Type make a great combo

* The block editor is flexible; whether you need easy access to tags when you're writing (they display in the sidebar), or if you want a screen free of distractions to do your best work, the block editor can do it with ease. And combined with Encrypted Post Type, you can confidently maximise the true potential of the block editor safe in the knowledge that your data is secure.
* WordPress is very mature and works great for managing lots of content - tags have been part of WordPress since 2008!
* The details pop-up (the i icon in the block editor toolbar) includes super useful information perfect for note taking, drafting documents and more!
* The block editor comes with some really smart keyboard shortcuts that can boost productivity. For example, highlight text and use CTRL+K (CMD+K on Mac) to add a link, or use CTRL+S (CMD+S on Mac) to save your work.

The WordPress block editor (also called Gutenberg) is an excellent writing tool. It's better than Microsoft Word at word processing (although that probably says more about Word), and is also a formidable website page builder (albeit a significant departure from WordPress of old). And it is so powerful, and has so much potential, that it could conceivably become the de-facto editor of the internet. It makes an excellent tool for taking notes, writing documents, and building web pages. And it has another trick up its sleeve that lends itself very well to encryption. Because of the need for Gutenberg to be backwards compatible with the rest of WordPress, the output of Gutenberg is simple html markup. Because the markup Gutenberg generates is so simple, almost all Gutenberg blocks are compatible with encryption.

### Pro version

If you want to make your content even more secure you can upgrade to the Pro version that includes an innovative way to manage your encryption keys called <a href="https://encryptedposttype.com/kb/rest-key-management-rkm/">REST Key Management (RKM)</a>. RKM stores your encryption keys on a separate WordPress site that you control, meaning that both the site where your encrypted content is stored AND the site where your keys are stored would have to be compromised for your data to be at risk - and it would have to be a very bad day for that to happen.

Included with Pro:

* Rest Key Management (RKM) - offers a significant security boost!
* Archive Posts - don't want a post to show up in 'All Posts'? Mark it as archived and it'll only be viewable in a special 'Archive' mode.
* Individual Posts - only the author of an individual post can view and edit it.
* Hide the front-end of your site - only use your WP site to write notes, or draft documents? Easily hide the front-end.
* Premium email support.

<a href="https://us1.onform.net/encryptedposttype/waitlist/">PRO VERSION COMING SOON - get on the waitlist*</a>

*Your email will only be used to let you know when the Pro version is available.

### Request a feature

The core plugin is available for anyone to contribute to on Github here: <a href="https://github.com/NewJenk/EPT/">github.com/NewJenk/EPT</a>, pull requests are welcome. In addition, you can <a href="https://us1.onform.net/encryptedposttype/request-a-feature/">request a feature by filling in the form here</a>.

### Compatibility with other plugins

Developer-friendly plugins can be extended to encrypt/decrypt content. Here are examples of how content can be encrypted and decrypted:

#### Encrypting content

See the method `en_p_t_encrypt_the_post` in encrypted-post-type.php for an example of how to encrypt content.

#### Decrypting content

See the example here: <a href="https://encryptedposttype.com/kb/about#decrypting-content">https://encryptedposttype.com/kb/about#decrypting-content</a>

== Installation ==

1. Install Encrypted Post Type either via the WordPress.org plugin repository or by uploading the files to your server.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Done!

== Frequently Asked Questions ==

= How can I hide a tag from the sidebar? =

By setting the description for the tag as 'Archived' or 'archived'.

= How can I tell if my content is encrypted? =

The quickest way to check is to change the view on the All Notes screen to 'Extended view'. You can do this by clicking 'Screen Options' in the top right of the screen, selecting the 'Extended view' radio and clicking 'Update'. You'll then see the content for each post display in the first column, if the content is encrypted then you'll see the ciphertext. You can turn off Extended view by selecting 'Compact view' and then clicking 'Update'.

= My content won't decrypt =

View the <a href="https://encryptedposttype.com/kb/troubleshooting/">troubleshooting tips here</a>.

= Can I use other plugins when using Encrypted Post Type? =

Yes, you can. But only install plugins that you are sure are safe to use and keep them up to date. This is particularly important when using WordPress to write and store potentially sensitive information. Please ensure you carefully consider whether you need to install a plugin and keep the number of plugins you use to a minimum - the fewer you use, the less likely it is that something will go wrong.

== Screenshots ==

1. Quickly see what you're currently working on as posts display most recently edited first, plus you can easily re-order posts by created date, and title
2. Use the powerful block editor (Gutenberg) to create any kind of note, document, to-do, project plan, you name it!
3. Click on a tag in the left-hand sidebar to see all the tagged posts, allowing you to easily organise large amounts of content

== Changelog ==

= 1.0.1 =
* Added do_action('en_p_t_before_save')
* Added do_action('en_p_t_after_revision_restore')
* No longer prepending ciphertext with 'en.p.t'

= 1.0.0 =
* Initial Release

== Upgrade Notice ==
 
= 1.0.0 =
* Initial Release