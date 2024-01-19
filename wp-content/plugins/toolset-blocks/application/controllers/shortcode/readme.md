Toolset requires to resolve some shortcodes in non conventional ways, for historical and feature reasons:
* We support some alternative syntax that replaces square brackets with `{!{'` and `}!}`.
* We have specific shortcodes that enable and disable formatting over their content.
* We have shortcodes to iterate over repeating elements, like repeating post fields and repeating taxonomy term fields.
* We support shortcodes as attribute values for other shortcodes.
* We need to resolve our internal shortcodes.
* We support shortcodes as values for HTML attributes.

Those features demand that we resolve our shortcodes earlier than the usual builtin mechanism in WordPress, which happens at ´the_content:11`.

In addition, we have our own formatting callbacks that replace `the_content` in a number of places, so we also need to resolve shortcodes in there.

The main entry point for resolving shortcodes is `OTGS\Toolset\Views\Controller\Shortcode\Resolution`. That controller is responsible for:
* Hooking into the right formatting methods to apply our logic, including native flows for rendering posts and widgets content.
* Instantiating the individual resolvers and applying them at the right time.

Each use case should be isolated in its own resolver, and registered in the `Store` for just-in-time instantiation.
