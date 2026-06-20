# Adapters

Adapters connect the chatbot to your system without hardcoding one database design.

## Generic PDO Adapter

Configure tables like this:

```php
[
  'table' => 'products',
  'label' => 'Products',
  'id_field' => 'id',
  'label_field' => 'name',
  'search_fields' => ['name', 'sku', 'description'],
  'url_pattern' => '/products.php?id={id}',
  'report' => true,
]
```

Rules:

- Use read-only tables or views.
- Expose only fields safe for the current user.
- Prefer database views for ERP data so access rules are enforced before the chatbot reads anything.
- Do not put passwords, tokens, private notes, or customer secrets in searchable fields.

## Custom Adapter

Implement `AdapterInterface` when you need custom logic for ERP reports, files, inventory, or calculations.
