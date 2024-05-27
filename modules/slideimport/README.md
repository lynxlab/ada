# ADA Slide Import Module

This module is designed to import from PDF and Office files into ADA nodes.
The module shall be only availabale for the __author__ user type to load some content in a few simple steps.

## Module Requirements

- ImageMagick 6.7 > (for PDF conversion to PNG)
- LibreOffice 4.0.4.2 > (for Office import support)

## WARNING

Due to the vulenrability addresseded [here](https://www.kb.cert.org/vuls/id/332928/) ImageMagick may not be able to perform the conversions.

Please refer to [this](https://stackoverflow.com/a/59193253) stackoverflow answer, in short you need to:

1. Make sure you have Ghostscript ≥9.24:

    ```bash
    gs --version
    ```

2. If yes, just remove this whole following section from `/etc/ImageMagick-6/policy.xml`:

    ```xml
    <!-- disable ghostscript format types -->
    <policy domain="coder" rights="none" pattern="PS" />
    <policy domain="coder" rights="none" pattern="PS2" />
    <policy domain="coder" rights="none" pattern="PS3" />
    <policy domain="coder" rights="none" pattern="EPS" />
    <policy domain="coder" rights="none" pattern="PDF" />
    <policy domain="coder" rights="none" pattern="XPS" />
    ```

__Details__: Removing just the line with pattern="PDF" inside would be enough to re-enable PDF conversion. On my computer, I removed the lines for the other PostScript-based filetypes as well just because I can't see a reason to prevent Image Magick from working with such files. (Talking about a personal computer only. On a web server, you might consider it dangerous as PostScript-based files can contain scripts … actually, PostScript is script.)
