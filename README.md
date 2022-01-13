Gifomattic is an Alfred workflow to help you organize and share your favorite GIF URLs.

To launch Gifomattic, open Alfred and enter the keyword `gif`.

## Installation
Please download the `Gifomattic.alfredworkflow` file from the [latest release.](https://github.com/chad1008/Gifomattic/releases) Once downloaded, open the file to install or update Gifomattic

**IMPORTANT:** Starting with Monterey, MacOS no longer ships with PHP built in, so you'll need to install it to use Gifomattic:

`brew install php`

If you don't already have homebrew, you can get started at https://brew.sh/.

## Adding GIFs To Your Library
There are three ways to add a new GIF to your library:

1. User-defined hotkey that will pull from your clipboard. Remember to copy your URL before hitting your hotkey!
2. After launching Gifomattic, paste your URL directly into the prompt
3. After launching Gifomattic, use the keyword `add` instead of a search term

After you've saved your URL and given it a name, you can optionally assign tags to the GIF to keep your library organized.

## Searching and Sharing Gifs
After you've added some GIFs to your library you can search for them by launching Gifomattic and typing your search term. Gifomattic will return any GIFs or tags that match your search string.

If you select a GIF from the search results, Gifomattic will paste that URL into your currently focused app.

If you select a tag from the search results, Gifomattic will select a random GIF that tag has been assigned to. This way you can ensure there's some variety to your GIF sharing... but it's also just a fun little surprise to see which GIF you get!

## Modifier Keys
Gifomattic uses three modifier keys: CMD, SHIFT, and CTRL.

**CMD** is used to explore more details about a GIF, preview a GIF in your browser, or browse/search all of the GIFs that a particular tag has been assigned to. Think of **CMD** as the "show me more" button.

**SHIFT** is used for editing GIFs or tags. This includes trashing GIFs and deleting tags, should you need to do so.

**CTRL** is used exclusively for permanently deleting GIFs from the trash. It serves no other function, to decrease the risk of accidental deletions.

## Explore Mode
You can explore your library using the **CMD** modfier. When used on a GIF, you'll be shown the name and URL of the GIF, and can preview the GIF by using the **CMD** modifier again. You'll also see how often the GIF has been shared, the date that you first saved it, and any tags that are assigned to it.

Explore mode for tags will list all of the GIFs that tag is currently assigned to. You can then search within that tag by entering a search term, share any individual GIF  from the list. You can also use **CMD** again to view GIF details.

## The Trash
Gifomattic allows you to safely delete your GIFs by placing them in the trash, rather than completely removing them right away.

Once trashed, a GIF will be permanently deleted automatically after 30 days.

**Important:** this deletion only happens if you actually *use* Gifomattic. When the workflow launches, it will check and clean up the trash, but it cannot do this if the workflow isn't run. In practice, this means that if you trash a GIF and then never run Gifomattic again, that GIF will stay in your database indefinitely. The next time you launch Gifommatic however (for example, on day 47 or something) the old GIFs in the trash will be cleaned up before your first search result even loads.

**Viewing the trash:**  To view GIFs currently in the trash, launch Gifomattic and then enter the `trash` keyword instead of a search term. From here you can empty the trash (delete all GIFs currently in the trash), or browse the currently trashed GIFs.

While browsing trashed GIFs, you'll then have the option to restore them to your library individually, or permanently delete individual GIFs using the **CTRL** modifier key.

