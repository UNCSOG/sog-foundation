# Relationships module

This directory contains the module for the complete management of (post) relationships. There are few guidelines
that **must** be followed to ensure the long-term sustainability of the whole Toolset codebase.

1. Outside of the module, only classes from the `\OTGS\Toolset\Common\Relationships\API` namespace may be directly referenced.
    - That means, for instantiating most classes, one needs to use the `Factory` class (which can be obtained directly
      or via DIC during plugin bootstrap process).
    - The only notable exception are namespace-less classes in the `API\PotentialAssociationQueryFilter` subdirectory
      which are already being used. But even those will safely renamed in the future.
    - It is possible that other classes are being needed but are not available via the namespace yet. In such case,
      consider filing a feature request to have this situation fixed.
2. Any code that in any way depends on a specific database structure must be part of the `DatabaseLayer` sub-namespace.
    - The database layer has different versions, where code belonging to a specific version must never refer to another
      version's code.
    - Version-agnostic code must never rely on a specific version, with the exception of `DatabaseLayerFactory`, which
      is to be used for obtaining version-specific instances (whose interfaces must again be version-agnostic).

Any exceptions to these rules must be **extremely well justified and documented.**
