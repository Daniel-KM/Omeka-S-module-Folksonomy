Folksonomy (module for Omeka S)
===============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Folksonomy] is a module for [Omeka S] that allows to add uncontrolled topics to
any resource (item, item set, media) and that allows visitors to tag them in
order to create a folksonomy or a tag cloud.

Tags can be added with or without captcha and approbation. Once approved, public
tags become normal tags. Tag creation and approbation are managed via roles.
They can be imported by the module [CSV Import] like in Omeka Classic.

This [Omeka S] module is a full and improved rewrite of the [Tagging plugin] for
[Omeka Classic].


Installation
------------

First, install the two optional modules [Generic] and [Blocks Disposition].

Uncompress files and rename module folder "Folksonomy".

Then install it like any other Omeka module and follow the config instructions.

The Folksonomy module can use Omeka ReCaptchas. You need to get keys to this
service and set them in the general preferences.

The tags (names) and the taggings (approval, reject) are manageable directly in
the admin view.


Display Tagging Form
--------------------

The tags and the tagging form are displayed automatically on item set, item or
media pages. To manage the display more precisely, use the module [Blocks Disposition],
or use the helpers to display them in your theme. The templates are customizable
in the theme. Rights and visibility are automatically managed.

* Important

The form uses jquery. It’s enabled by default in the admin view, but may be not
available in some themes.


Internal Notes
--------------

The tables added to the database support the possibility for each user to tag
each resource and to manage his/her own taggings, but currently, the forms
manage only one tagging for all users for a tag for one resource, because Omeka
is document-oriented.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2013-2021 (see [Daniel-KM] on GitLab)

First version of this module has been built as a plugin for Omeka Classic for
the digital library of [Mines ParisTech].


[Folksonomy]: https://gitlab.com/Daniel-KM/Omeka-S-module-Folksonomy
[Omeka S]: https://omeka.org/s
[Tagging plugin]: https://gitlab.com/Daniel-KM/Omeka-plugin-Tagging
[CSV Import]: https://github.com/omeka-s-modules/CSVImport
[Omeka Classic]: https://omeka.org
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Folksonomy/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Mines ParisTech]: https://patrimoine.mines-paristech.fr
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
