# Gifomattic

Gifomattic is an Alfred 3 workflow to help you organize and share your favorite GIFs.

There are four commands (so far). Gifomattic uses URLs for GIF sharing. You can use the URL where you originally found the GIF or upload it to someplace of your own like a Cloudup stream or WordPress media library.

##Adding GIFs (keyword: gifadd)

Entering the keyword 'gifadd' into Alfred will allow you to save a new GIF. The first time your run gifadd, Gifomattic will create a new folder for your data. In that folder a new database with the necessary table is built and a folder for icon files is created, along with the icon for the GIF you've just saved.

After typing gifadd, you provide a url and name (both required) for the GIF, as well as optional tags.The tags are separated by comas.

You can also assign a hotkey to gifadd, which will start the process using the last item copied to your clipboard. Copy URL, hit hotkey, fill in title and tags and you're done!

##Searching and Sharing GIFs (keyword: gif)

Now that you've saved a GIF or two, you can select them to share the URLs.

Enter the keyword 'gif' into Alfred and then start typing to search. Gifomattic will search for GIFs whose names or URLs match what you've entered. You'll see the name of your GIFs, followed by their URL. Each one will also have an autogenerated thumbnail icon.

When you see the GIF you want, select it to paste the URL into your focused app.

This keyword also searches for tags. If your search query matches any of the tags you've assigned, you'll see them listed. Each tag will display the number of GIFs available in that group.

Selecting a tag from the search results will paste the URL of a random tag from that group - making it a nice surprise for you, as well as the person you're sending it to :)


##Editing  GIFs (keyword: gifedit)


If you need to change anything about your GIF, you can use 'gifedit.'

This will run a similar search to what you've used before, only this time you'll see the ID number of the GIF displayed in the results.

Selecting a GIF from this search will allow you to edit the selected GIF. At each step along the way you can elect to keep the current value, or enter a new one. For tags you'll also have the option (when applicable) to erase the current tags.

You can also assign a hotkey to gifedit, which will grab the current selection in OSX - ideal for quickly fixing a GIF that didn't paste properly.


##Deleting GIFs (keyword: gifdelete)


This one's fairly self explanatory. Enter 'gifdelete' then search and select.

You then confirm the cancellation (or not, if you've changed your mind) and the GIF is removed from your database. There no undo button. Scary stuff.
