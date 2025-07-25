<?php

namespace App\Services;

use Faker\Factory as Faker;
use App\Models\DocumentType;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SchemaBuilder
{
    protected const FIELD_TYPE_CONFIG = [
        'text' => [
            'type' => 'varchar',
            'maxLength' => 255,
            'default' => null
        ],
        'number' => [
            'type' => 'bigint',
            'maxLength' => 20,
            'max' => PHP_INT_MAX,
            'min' => PHP_INT_MIN,
            'step' => null,
            'default' => null
        ],
        'date' => [
            'type' => 'date',
            'maxLength' => null,
            'default' => null
        ],
        'time' => [
            'type' => 'time',
            'maxLength' => null,
            'default' => null
        ],
        'datetime' => [
            'type' => 'datetime',
            'maxLength' => null,
            'default' => null
        ],
        'email' => [
            'type' => 'varchar',
            'maxLength' => 254,
            'default' => null
        ],
        'url' => [
            'type' => 'varchar',
            'maxLength' => 255,
            'default' => null
        ],
        'phone' => [
            'type' => 'varchar',
            'maxLength' => 25,
            'default' => null
        ],
        'select' => [
            'type' => 'enum',
            'maxLength' => null,
            'default' => null
        ],
        'textarea' => [
            'type' => 'text',
            'maxLength' => null,
            'default' => null
        ],
    ];

    protected const MAX_LEGTH_FOR_FIELD_NAME = 64;
    protected const REQUIRED_FORM_KEYS = ['name', 'type', 'required', 'rules'];
    protected const REQUIRED_FORM_KEYS_UPDATE = ['id', 'sequence_number'];
    protected const REQUIRED_TABLE_KEYS = ['id', 'type', 'maxLength', 'default', 'nullable', 'unique', 'attribute', 'updated_at'];
    protected const REQUIRED_TABLE_KEYS_UPDATE = ['sequence_number', 'created_at'];
    protected const REQUIRED_TABLE_KEY_FOR_ENUM_FIELD = 'enumValues';

    /**
     * Returns the configuration for different field types.
     *
     * This function returns an associative array containing the configuration
     * for different field types. The array keys are the field types and the
     * values are associative arrays containing the configuration for each type.
     *
     * @param string $type The field type to get the configuration for.
     * @return array The configuration for different field types.
     */
    public static function __get_field_type_config(string $type): array
    {
        if ($type && Arr::has(self::FIELD_TYPE_CONFIG, $type)) {
            return self::FIELD_TYPE_CONFIG[$type];
        } else {
            return self::FIELD_TYPE_CONFIG;
        }

        return self::FIELD_TYPE_CONFIG;
    }

    /**
     * Gets the maximum length of a field name.
     *
     * @return int The maximum length of a field name.
     */
    public static function __get_max_length_for_field_name(): int
    {
        return self::MAX_LEGTH_FOR_FIELD_NAME;
    }

    /**
     * Validates the structure of a given schema.
     *
     * This function checks if a given schema array is empty, and if each field
     * in the schema has all the required keys. The required keys: name, type, required, rules, unique (optional).
     * If any of the checks fail, an exception is thrown with a meaningful error
     * message.
     * 
     * @param array $schema The schema to validate.
     * @param string $action The action to perform on the schema ('create' or 'update').
     * @return void
     *
     * @throws InvalidArgumentException When schema is empty or missing required keys
     * @throws DomainException When field structure is invalid for update action
     */
    public static function validate_structure_of_form_schema(array $schema, string $action = 'create')
    {
        if (empty($schema))
            throw new \InvalidArgumentException("Empty schema provided. Schema must contain at least one field definition.", Response::HTTP_BAD_REQUEST);

        foreach ($schema as $field) {
            // Check if each field has all the required keys
            if (!Arr::hasAny($field, self::REQUIRED_FORM_KEYS))
                throw new \InvalidArgumentException(
                    sprintf(
                        "Invalid schema structure. Each field must contain the following required keys: '%s'. Field data: \n%s.",
                        Arr::join(self::REQUIRED_FORM_KEYS, '\', \'', '\', and \''),
                        json_encode($field, JSON_PRETTY_PRINT)
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            // Check if each field has all the required keys if action is update
            if ($action === 'update' && !Arr::hasAny($field, self::REQUIRED_FORM_KEYS_UPDATE))
                throw new \DomainException(
                    sprintf(
                        "Missing update mode required keys. Fields in update mode must contain: '%s'. Field data: \n%s.",
                        Arr::join(self::REQUIRED_FORM_KEYS_UPDATE, '\', \'', '\', and \''),
                        json_encode($field, JSON_PRETTY_PRINT)
                    ),
                    Response::HTTP_BAD_REQUEST
                );
        }
    }

    /**
     * Validates the structure of a table schema.
     *
     * This function checks if the provided schema array for a table is empty
     * and verifies that each field in the schema contains all the required keys: id, sequence_number, type, maxLength, default, nullable, unique, attribute, updated_at.
     * If any field is missing a required key or if the schema is empty, an exception 
     * is thrown with an appropriate error message.
     *
     * @param array $schema The table schema to validate.
     * @param string $action The action to perform, either 'create' or 'update'.
     * @return void
     *
     * @throws InvalidArgumentException When schema is empty
     * @throws LogicException When schema structure is invalid
     * @throws UnexpectedValueException When field name is missing
     */
    public static function validate_structure_of_table_schema(array $schema, string $action = 'create')
    {
        if (empty($schema))
            throw new \InvalidArgumentException("Empty table schema provided. Schema must contain at least one column definition.", Response::HTTP_BAD_REQUEST);

        foreach ($schema as $field => $value) {
            // check if field is empty or missing required keys
            if (empty($field))
                throw new \UnexpectedValueException("Missing field name in schema. Each field must have a valid name identifier.", Response::HTTP_BAD_REQUEST);

            // check if field has all required keys
            if (!Arr::hasAny($value, self::REQUIRED_TABLE_KEYS))
                throw new \LogicException(
                    sprintf(
                        "Invalid table schema structure. Field '%s' is missing required keys: '%s'. Provided keys: '%s'.",
                        $field,
                        Arr::join(self::REQUIRED_TABLE_KEYS, '\', \'', '\', and \''),
                        Arr::join(array_keys($value), '\', \'', '\', and \'')
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            // check if enum field has enumValues
            if ($value['type'] === 'enum' && !Arr::exists($value, self::REQUIRED_TABLE_KEY_FOR_ENUM_FIELD))
                throw new \LogicException(
                    sprintf(
                        "Missing enum values for field '%s'. Enum fields must specify allowed values using the '%s' key.",
                        $field,
                        self::REQUIRED_TABLE_KEY_FOR_ENUM_FIELD
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            // check if field has all required keys if action is update
            if ($action === 'create' && !Arr::hasAny($value, self::REQUIRED_TABLE_KEYS_UPDATE))
                throw new \LogicException(
                    sprintf(
                        "Missing create mode required keys for field '%s'. Required keys: '%s'.",
                        $field,
                        Arr::join(self::REQUIRED_TABLE_KEYS_UPDATE, '\', \'', '\', and \'')
                    ),
                    Response::HTTP_BAD_REQUEST
                );
        }
    }


    /**
     * Validates the name of an attribute.
     * 
     * @param string $name The name of the attribute to validate.
     * @return void
     * 
     * @throws InvalidArgumentException When name is empty
     * @throws LengthException When name exceeds maximum length
     * @throws UnexpectedValueException When name format is invalid     
     */
    public static function validate_attribute_name(string $name)
    {
        // check if name is empty
        if (empty($name))
            throw new \InvalidArgumentException("Attribute name cannot be empty.", Response::HTTP_BAD_REQUEST);

        if (Str::length($name) > self::MAX_LEGTH_FOR_FIELD_NAME)
            throw new \LengthException(
                sprintf(
                    "Attribute name '%s' exceeds maximum length of %s characters. Current length: %s.",
                    $name,
                    self::MAX_LEGTH_FOR_FIELD_NAME,
                    Str::length($name)
                ),
                Response::HTTP_BAD_REQUEST
            );

        // Validate attribute name, format that can only contain letters, numbers, and underscores, as well as spaces. can only be up to 64 characters
        if (!Str::of($name)->isMatch('/^(?!.* {2})[a-zA-Z][a-zA-Z0-9_\s]{0,' . self::MAX_LEGTH_FOR_FIELD_NAME - 1 . '}$/')) {
            throw new \UnexpectedValueException(
                sprintf(
                    "Invalid attribute name format: '%s'. Name must: start with a letter, contain only letters/numbers/underscores/single spaces, be 1-%s characters long.",
                    $name,
                    self::MAX_LEGTH_FOR_FIELD_NAME
                ),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Validates and processes a given schema array for both table and form.
     *
     * This function validates the schema structure and builds a table schema
     * and a form schema from the given array. It handles various input types
     * such as text, number, date, time, datetime, url, email, phone, textarea,
     * and select. The function also handles the generation of input attributes,
     * input modes, and required fields, as well as optional instructions and
     * default values.
     *
     * @param array $schema The array containing field definitions for the table and form.
     * @param string $action The action to perform, either 'create' or 'update'.
     * @return array An associative array containing the table schema and the form schema.
     * 
     * @throws InvalidArgumentException When schema is empty
     * @throws OutOfBoundsException When field type is invalid
     * @throws LogicException When field structure is invalid
     */
    public static function handle_schema_for_table_and_form(array $schema, string $action = 'create')
    {
        if (empty($schema)) throw new \InvalidArgumentException("Sorry, we couldn't find any of your schema. Please try again and create a valid schema.", Response::HTTP_BAD_REQUEST);

        // Validate schema structure
        self::validate_structure_of_form_schema($schema, $action);

        // validate same name or duplicate name of attribute
        self::validate_unique_name_for_attribute(array_column($schema, 'name'));

        $columns = [];
        $attributes = [];
        $sequence_number = 1;

        foreach ($schema as $field) {
            $type_config = self::FIELD_TYPE_CONFIG[$field['type']] ?? null;

            if (is_null($type_config))
                throw new \OutOfBoundsException(
                    sprintf(
                        "Invalid field type '%s'. Please use one of the following: %s.",
                        $field['type'],
                        implode(', ', array_keys(self::FIELD_TYPE_CONFIG))
                    ),
                    Response::HTTP_BAD_REQUEST
                );


            $field_name = Str::of($field['name'])->snake()->trim()->toString();
            self::validate_attribute_name($field['name']);

            $column = [
                'id' => $action === 'create' ? (strtolower(Str::random(10)) . ":$sequence_number") : $field['id'],
                'sequence_number' => $action === 'create' ? $sequence_number : $field['sequence_number'],
                'type' => $type_config['type'],
                'maxLength' => (int)strlen($type_config['maxLength']),
                'default' => $type_config['default'],
                'nullable' => !$field['required'],
                'unique' => $field['unique'] ?? false,
                'attribute' => null,
                'updated_at' => now('Asia/Jakarta'),
            ];

            if ($action === 'create') {
                $column['created_at'] = now('Asia/Jakarta');
            }

            if (Arr::has($field, 'rules.max') && is_numeric($field['rules']['max'])) {
                $max_length = (int)strlen($field['rules']['max']);

                $column['maxLength'] = $max_length > $column['maxLength'] ? $max_length : $column['maxLength'];
            }

            // Handle custom maxLength from rules
            if (Arr::has($field, 'rules.maxLength') && is_numeric($field['rules']['maxLength'])) {
                $max_length = (int)$field['rules']['maxLength'];
                if ($field['type'] === 'text' && $max_length > 255) {
                    $column['type'] = 'text';
                    $column['maxLength'] = null;
                }
            }

            // Handle default value from rules
            if (Arr::has($field, 'rules.defaultValue')) {
                $column['default'] = $field['rules']['defaultValue'];
            }

            // Handle ENUM options for select type
            if ($field['type'] === 'select' && Arr::has($field, 'rules.options')) {
                $options = Arr::map(explode("\n", $field['rules']['options']), 'trim');
                $column['enumValues'] = $options;
            };

            $columns[$field_name] = $column;

            $schema_form = self::create_schema_form($action, $field, $field_name, $column['id'], ($action === 'update' ? $column['sequence_number'] : $sequence_number));
            $attributes[$schema_form['attribute_name']] = $schema_form['attribute'];

            $sequence_number++;
        }

        if (empty($columns) || empty($attributes)) throw new \LogicException("Sorry, we couldn't find any of your schema. Please try again and create a valid schema.", Response::HTTP_BAD_REQUEST);

        return [
            'table' => $columns,
            'form' => $attributes
        ];
    }

    /**
     * Builds a form schema from the given schema array.
     *
     * This function validates the structure of the provided schema for
     * a form, constructs attributes with their respective rules, and 
     * returns the successfully built schema. It handles specific types 
     * such as 'select' for ENUM options and 'number' for numeric rules.
     * And status parameter must 'create' or 'update'.
     * 
     * @param string $action The action to perform, either 'create' or 'update'.
     * @param array $field The array containing schema definitions for 
     *                      form fields.
     * @param string $field_name The name of the form field.
     * @param string $id The ID of the form field.
     * @param int|null $sequence_number The sequence number of the form field.
     * 
     * @return array An associative array containing a success status,
     *               message, and the constructed form schema if valid,
     *               otherwise an error message.
     * 
     * @throws InvalidArgumentException When schema is empty or missing required keys
     */
    private static function create_schema_form(string $action, array $field, string $field_name, string $id, ?int $sequence_number = null)
    {
        // check if status is create and attribute id and sequence number is not empty
        if (!in_array($action, ['create', 'update']) || empty($field) || empty($field_name)) throw new \InvalidArgumentException("Sorry, we couldn't find any of your schema. Please try again and create a valid schema.", Response::HTTP_BAD_REQUEST);

        if ($action === "create" && (empty($id) || empty($sequence_number))) throw new \InvalidArgumentException("Sorry, we couldn't find attribute id and sequence number of your schema. Please try again or create a valid schema.", Response::HTTP_BAD_REQUEST);
        if ($action === "update" && empty($id)) throw new \InvalidArgumentException("Sorry, we couldn't find attribute id of your schema. Please try again and create a valid schema.", Response::HTTP_BAD_REQUEST);

        $attribute_name = Str::of($field['name'])->trim()->toString();

        $attribute = [
            'name' => $field_name,
            'type' => $field['type'],
            'required' => $field['required'] ?? false,
            'unique' => $field['unique'] ?? false,
            'rules' => $field['rules'] ?? [],
            'updated_at' => now('Asia/Jakarta'),
        ];

        // Handle custom maxLength from rules
        if (Arr::has($field, 'rules.maxLength') && is_numeric($field['rules']['maxLength'])) {
            $max_length = (int)$field['rules']['maxLength'];
            if (($field['type'] === 'text' || $field['type'] === 'textarea') && $max_length > 65535) {
                $attribute['rules']['maxLength'] = 65535;
            } else if (($field['type'] === 'text' || $field['type'] === 'textarea') && $max_length < -65535) {
                $attribute['rules']['maxLength'] = -65535;
            } else {
                $attribute['rules']['maxLength'] = $max_length;
            }
        }

        // Handle custom minLength from rules
        if (Arr::has($field, 'rules.minLength') && is_numeric($field['rules']['minLength'])) {
            $min_length = (int)$field['rules']['minLength'];
            if (($field['type'] === 'text' || $field['type'] === 'textarea') && $min_length < -65535) {
                $attribute['rules']['minLength'] = 65535;
            } else if (($field['type'] === 'text' || $field['type'] === 'textarea') && $min_length > 65535) {
                $attribute['rules']['minLength'] = -65535;
            } else {
                $attribute['min'] = $min_length;
            }
        }

        // Handle ENUM options for select type
        if ($field['type'] === 'select' && Arr::has($field, 'rules.options') && is_string($field['rules']['options']) && trim($field['rules']['options']) !== '') {
            $options = Arr::map(explode("\n", $field['rules']['options']), 'trim');
            $attribute['rules']['options'] = $options;
        }

        // Handle NUMBER type 
        if (
            $field['type'] === 'number' && Arr::has($field, 'rules.min') && Arr::has($field, 'rules.max') && Arr::has($field, 'rules.step')
            && is_numeric($field['rules']['min']) && is_numeric($field['rules']['max']) && is_numeric($field['rules']['step'])
        ) {
            if (Arr::has($field, 'rules.max') && is_numeric($field['rules']['max'])) {
                $max_length = (int)strlen($field['rules']['max']);

                // check if max length is greater than max length of type
                if ($max_length > self::FIELD_TYPE_CONFIG[$field['type']]['max']) {
                    $attribute['rules']['max'] = self::FIELD_TYPE_CONFIG[$field['type']]['max'];
                } else if ($max_length < self::FIELD_TYPE_CONFIG[$field['type']]['min']) {
                    $attribute['rules']['max'] = self::FIELD_TYPE_CONFIG[$field['type']]['min'];
                } else {
                    $attribute['rules']['max'] = $max_length;
                }
            }

            if (Arr::has($field, 'rules.min') && is_numeric($field['rules']['min'])) {
                $min_length = (int)strlen($field['rules']['min']);

                // check if min length is greater than min length of type 
                if ($min_length > self::FIELD_TYPE_CONFIG[$field['type']]['max']) {
                    $attribute['rules']['min'] = self::FIELD_TYPE_CONFIG[$field['type']]['max'];
                } else if ($min_length < self::FIELD_TYPE_CONFIG[$field['type']]['min']) {
                    $attribute['rules']['min'] = self::FIELD_TYPE_CONFIG[$field['type']]['min'];
                } else {
                    $attribute['rules']['min'] = $min_length;
                }
            }

            if (Arr::has($field, 'rules.step') && is_numeric($field['rules']['step'])) {
                $step_length = (int)strlen($field['rules']['step']);

                // check if step length is greater than step length of type
                if ($step_length > self::FIELD_TYPE_CONFIG[$field['type']]['max']) {
                    $attribute['rules']['step'] = self::FIELD_TYPE_CONFIG[$field['type']]['max'];
                } else if ($step_length < self::FIELD_TYPE_CONFIG[$field['type']]['min']) {
                    $attribute['rules']['step'] = self::FIELD_TYPE_CONFIG[$field['type']]['min'];
                } else {
                    $attribute['rules']['step'] = $step_length;
                }
            }
        }

        // Handle INSTRUCTIONS rule
        if (Arr::has($field, 'rules.instructions') && is_string($field['rules']['instructions']) && trim($field['rules']['instructions']) !== '') {
            $field['rules']['instructions'] = strip_tags($field['rules']['instructions']);
        }

        // if status is create add sequence_number and created_at attribute
        if ($action === "create") $attribute = Arr::add($attribute, 'created_at', now('Asia/Jakarta'));

        // adding attribute id to attribute
        $attribute = array_merge(['id' => $id, 'sequence_number' => $sequence_number], $attribute);

        return [
            'attribute_name' => $attribute_name,
            'attribute' => $attribute
        ];
    }

    /**
     * Generates HTML for a form based on the provided schema array.
     *
     * This function validates the schema structure and builds an HTML form
     * with fields and attributes defined in the schema. It supports various
     * input types such as text, number, date, time, datetime, url, email, phone,
     * textarea, and select. The function handles the generation of input attributes,
     * input modes, and required fields, as well as optional instructions and default
     * values.
     *
     * @param array $schema_form The array containing field definitions for the form.
     * @param ?object $document_type_data The data object for the document type (if any).
     * @param string $action The action of the form, either 'create', 'update', or 'insert'.
     *               'create' is for creating a new form, 'update' is for updating an existing form, and 'insert' is for inserting a new data/collecting data from file.
     * @param ?string $file_id The id of file data that will be inserted, this is required when action is 'insert'
     * @return string The generated HTML form.
     * 
     * @throws InvalidArgumentException When schema is empty
     * @throws OutOfBoundsException When field type is invalid
     */
    public static function create_form_html(array $schema_form, ?object $document_type_data = null, string $action = 'create', $file_id = null)
    {
        // Check if schema is empty
        if (empty($schema_form)) throw new \InvalidArgumentException("Sorry, we couldn't find any of your schema. Please try again and create a valid schema.", Response::HTTP_BAD_REQUEST);

        // Validate schema structure
        self::validate_structure_of_form_schema($schema_form);

        // reorder schema
        $schema_form = self::reorder_schema_sequence_number($schema_form);

        // Function to generate input attributes based on rules
        $generate_attributes = function (array $rules, string $type, bool $required) {
            $attributes = [];
            foreach ($rules as $key => $value) {
                if (in_array($key, ['min', 'max', 'step', 'minLength', 'maxLength']) && is_numeric($value)) {
                    $attributes[] = "{$key}=\"{$value}\"";
                } elseif ($key === 'defaultValue' && ($type !== 'textarea' && $type !== 'select') && !empty($value)) {
                    $attributes[] = "value=\"{$value}\"";
                } elseif (in_array($key, ['minDate', 'maxDate', 'minTime', 'maxTime', 'minDateTime', 'maxDateTime']) && !empty($value)) {
                    if (strpos($key, 'min') !== false && !empty($value)) {
                        $attr = 'min';
                    } elseif (strpos($key, 'max') !== false && !empty($value)) {
                        $attr = 'max';
                    }

                    $attributes[] = "{$attr}=\"{$value}\"";
                }
            }
            if ($required) {
                $attributes[] = 'aria-required="true" required';
            }
            return implode(' ', $attributes);
        };

        // Helper function to map input modes
        $get_input_mode = fn(string $type) => match ($type) {
            'number' => 'inputmode="numeric"',
            'email' => 'inputmode="email"',
            'phone' => 'inputmode="tel"',
            'url' => 'inputmode="url"',
            default => '',
        };

        $FORM_HTML = '';
        if ($action === 'insert') {
            // insert input hidden for file attachment id
            $FORM_HTML .= "<input type=\"hidden\" name=\"file_id[]\" value=\"{$file_id}\" aria-hidden=\"true\" />";
        } else if ($action === 'update') {
            // insert input hidden for data id
            $FORM_HTML .= "<input type=\"hidden\" name=\"id[]\" value=\"{$document_type_data->id}\" aria-hidden=\"true\" />";
        }

        $field_name = '';
        foreach ($schema_form as $field => $data) {
            // Getting the value if exists
            $field_name = $data['name'];
            $field_value = $document_type_data ? $document_type_data->$field_name : old($field_name);

            // Validate field type
            $type_config = self::FIELD_TYPE_CONFIG[$data['type']] ?? null;
            if (!$type_config)
                throw new \OutOfBoundsException(
                    sprintf(
                        "Invalid field type %s'. Please use one of the following: %s.",
                        $data['type'],
                        implode(', ', array_keys(self::FIELD_TYPE_CONFIG))
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            // Generate attributes
            $attributes = $generate_attributes($data['rules'], $data['type'], $data['required']);
            $inputMode = $get_input_mode($data['type']);
            $label = ucfirst($field) . ($data['required'] ? ' <span class="text-danger">*</span>' : '');
            $title = $data['rules']['instructions'] ? "title=\"{$data['rules']['instructions']}\"" : '';
            $is_unique = $data['unique'] ? 'true' : 'false';

            // Form group wrapper
            $FORM_HTML .= "<div class=\"form-group row g-1 mb-3\">
                    <label class=\"form-label col-sm-sm-3\" for=\"$field_name\">$label</label>
                    <div class=\"col-sm-sm-9\">";

            // Field type handling
            switch ($data['type']) {
                case 'textarea':
                    $FORM_HTML .= "<textarea class=\"form-control\" name=\"{$field_name}[]\" id=\"$field_name\" rows=\"3\" data-unique=\"$is_unique\" $title placeholder=\"Enter $field\" $attributes>$field_value</textarea>";
                    break;

                case 'select':
                    $options = "<option value=\"\" disabled " . ($document_type_data === null ? 'selected' : '') . ">Choose...</option>"
                        . array_reduce(
                            $data['rules']['options'] ?? [],
                            function ($html, $option) use ($document_type_data, $field_value) {
                                if ($document_type_data != null) {
                                    $_selected = $field_value == $option ? 'selected' : '';
                                    $html .= "<option value=\"{$option}\" $_selected>{$option}</option>";
                                } else {
                                    $html .= "<option value=\"{$option}\">{$option}</option>";
                                }
                                return $html;
                            },
                            ''
                        );

                    $FORM_HTML .= "<select class=\"form-select\" name=\"{$field_name}[]\" id=\"$field_name\" data-unique=\"$is_unique\" $title $attributes>$options</select>";
                    break;

                default:
                    $input_type = $data['type'] === 'datetime' ? 'datetime-local' : $data['type'];
                    $number_input_pattern = "^-?[0-9]+$";
                    $FORM_HTML .= "<input type=\"$input_type\" value=\"$field_value\" class=\"form-control\" name=\"{$field_name}[]\" id=\"$field_name\"" . ($data['type'] === 'number' ? " pattern=\"{$number_input_pattern}\"" : '') . " data-unique=\"$is_unique\" $title $inputMode placeholder=\"Enter $field\" $attributes />";
            }

            // Add instructions if present
            if ($data['rules']['instructions']) {
                $FORM_HTML .= "<p class=\"form-text text-muted\">{$data['rules']['instructions']}</p>";
            }

            $FORM_HTML .= "</div></div>";
        }

        return $FORM_HTML;
    }

    /**
     * Generates HTML table rows for schema attributes.
     *
     * This function processes a given schema form and generates HTML table rows
     * for each attribute. It validates the schema, sorts it by sequence number,
     * and formats the attribute data including rules, options, and other metadata.
     * It also constructs links for editing and deleting attributes.
     *
     * @param string $name The name of the document type associated with the schema.
     * @param array $schema_form An array representing the schema form, containing
     *                           the attributes and their configurations.
     *
     * @return string The generated HTML string for the table rows.
     *
     * @throws \InvalidArgumentException If the schema form is empty.
     */
    public static function create_table_row_for_schema_attributes_in_html(string $name, array $schema_form)
    {
        // check if schema is empty
        if (empty($schema_form)) throw new \InvalidArgumentException("Schema attribute for document type {$name} is empty. Please add at least one field/attribute to the schema.", Response::HTTP_BAD_REQUEST);

        // Validate schema structure
        self::validate_structure_of_form_schema($schema_form);

        // Sort by sequence_number
        $schema_form = self::sort_schema_by_sequence_number($schema_form);

        // rows 
        $rows = '';
        $loop_index = 1;
        foreach ($schema_form as $field => $data) {
            $data['required'] = $data['required'] ? '<span class="badge bg-success">yes' : '<span class="badge bg-danger">no' . '</span>';
            $data['unique'] = $data['unique'] ? '<span class="badge bg-success">Yes' : '<span class="badge bg-danger">No' . '</span>';
            $data['created_at'] = Carbon::parse($data['created_at'], 'Asia/Jakarta')->format('d F Y, H:i A');
            $data['updated_at'] = Carbon::parse($data['updated_at'], 'Asia/Jakarta')->format('d F Y, H:i A');

            // strip html tags and escape single quotes or double quotes for 'instructions' attribute
            $data['instructions'] = Str::of($data['rules']['instructions'] ?? '')->stripTags()->replaceMatches('/["\']/', '')->toString();

            // define rules html
            $rule_labels = [
                'instructions' => 'Instructions',
                'options' => 'Options',
                'defaultValue' => 'Default value',
                'minLength' => 'Minimum length',
                'maxLength' => 'Maximum length',
                'minDate' => 'Minimum date',
                'maxDate' => 'Maximum date',
                'minTime' => 'Minimum time',
                'maxTime' => 'Maximum time',
                'minDateTime' => 'Minimum date and time',
                'maxDateTime' => 'Maximum date and time',
                'min' => 'Minimum value',
                'max' => 'Maximum value',
                'step' => 'Step value'
            ];

            $format_rules = [
                'minDate' => 'd F Y',
                'maxDate' => 'd F Y',
                'minTime' => 'H:i A',
                'maxTime' => 'H:i A',
                'minDateTime' => 'd F Y h:i A',
                'maxDateTime' => 'd F Y h:i A'
            ];

            $rules = sprintf(
                '<div class="dialog-content-metadata">%s</div>',
                implode('', array_map(function ($key, $value) use ($rule_labels, $format_rules) {
                    if (!Arr::has($rule_labels, $key)) return '';

                    $formatted_value = $value ?? 'Not defined';
                    if (Arr::has($format_rules, $key) && $value) {
                        $formatted_value = \Carbon\Carbon::parse($value)->format($format_rules[$key]);
                    } elseif ($key === 'options') {
                        $formatted_value = implode('', array_map(fn($option) => "<span class=\"badge bg-secondary border\">$option</span>", $value));
                    } elseif (in_array($key, ['minLength', 'maxLength'])) {
                        $formatted_value = $value ? "$value characters" : 'Not defined';
                    }

                    return "<div class=\"meta-item\">
                                <div class=\"meta-label\">{$rule_labels[$key]}:</div>
                                <div class=\"meta-value\">$formatted_value</div>
                            </div>";
                }, array_keys($data['rules'] ?? []), $data['rules'] ?? [])) // passing key and value separately from array rules
            );

            $popover_id = Str::of($field)->lower()->replace(' ', '-')->toString();
            $popover_content = "<dialog class=\"dialog-wrapper\" role=\"tooltip\" popover id=\"rules-dialog-$popover_id\" aria-label=\"Rules wrapper for $field\" title=\"Rules wrapper for $field\">
                        <span class=\"position-absolute p-2 top-0 start-50 translate-middle badge rounded border border-2 border-dark bg-white shadow-sm text-dark\" aria-hidden=\"true\">Rules for attribute: $field.</span>
                        <div class=\"dialog-section pt-2\" aria-labelledby=\"dialog-label-$popover_id\">
                            <h3 class=\"visually-hidden\" id=\"#dialog-label-$popover_id\" aria-hidden=\"true\">Rules for attribute: $field.</h3>

                            <div class=\"dialog-content\">$rules</div>
                        </div>
                    </dialog>";

            $rows .= "<tr 
                    aria-rowindex=\"$loop_index\" 
                    aria-expanded=\"false\"
                    role=\"row\"
                    popovertarget=\"#rules-dialog-$popover_id\"
                    title=\"Click to open rules for attribute $field.\"
                    >
                        <td class=\"text-nowrap\">$loop_index</td>
                        <td class=\"text-nowrap\">$field</td>
                        <td class=\"text-nowrap\" translate=\"no\">{$data['type']}</td>
                        <td class=\"text-nowrap\">{$data['required']}</td>
                        <td class=\"text-nowrap\">{$data['unique']}</td>
                        <td class=\"text-nowrap\"><time datetime=\"{$data['created_at']}\">{$data['created_at']}</time></td>
                        <td class=\"text-nowrap\"><time datetime=\"{$data['updated_at']}\">{$data['updated_at']}</time></td>
                        <td class=\"text-nowrap\">
                            <a href=\"" . route('documents.edit.schema', [$name, $data['id']]) . "\" role=\"button\" class=\"btn btn-warning btn-sm btn-edit-attribute\" title=\"Button: to edit attribute {$field} from document type {$name}.\" data-id=\"{$data['id']}\"><i class=\"bi bi-pencil fs-5\"></i></a>
                            <form action=\"" . route('documents.delete.schema', [$name, $data['id']]) . "\" class=\"form-delete-attribute d-inline\" method=\"post\" data-id=\"{$data['id']}\" data-name=\"$field\">
                                " . csrf_field() . "

                                <input type=\"hidden\" name=\"_method\" value=\"DELETE\">
                                <button type=\"submit\" role=\"button\" class=\"btn btn-danger btn-sm btn-delete-attribute\" title=\"Button: to delete attribute {$field} from document type {$name}.\"><i class=\"bi bi-trash fs-5\"></i></button>
                            </form>
                        </td>
                    </tr>

                    $popover_content";

            $loop_index++;
        }

        return $rows;
    }

    /**
     * Sorts the given schema array by sequence number.
     * 
     * The function accepts a schema array and sorts it based on the
     * sequence number of each attribute. The sorting is done in-place
     * and the sorted schema array is returned after sorting.
     * 
     * @param array $schema The array containing schema definitions for 
     *                      document type attributes.
     * 
     * @return array The sorted schema array.
     */
    public static function sort_schema_by_sequence_number(array $schema)
    {
        uasort($schema, function ($a, $b) {
            return $a['sequence_number'] <=> $b['sequence_number'];
        });

        return $schema;
    }

    /**
     * Generates the HTML for the table header (thead) based on the provided schema.
     *
     * The function extracts fields from the schema, sorts them by their sequence number,
     * and constructs an HTML table header row. Each field name is used as a column header.
     * Additional columns for "Created At", "Updated At", and "Action" are appended at the end.
     *
     * @param array $schema_form An array representing the schema with field definitions.
     * 
     * @return string The generated HTML for the table header.
     * 
     * @throws \InvalidArgumentException if the schema is empty.
     */
    public static function create_table_thead_from_schema_in_html(string $table_name, array $schema_form)
    {
        // Check if schema is empty
        if (empty($schema_form)) throw new \InvalidArgumentException("Schema attribute for document type '$table_name' is empty. Please add at least one attribute to the schema.", Response::HTTP_BAD_REQUEST);

        // Validate schema structure
        self::validate_structure_of_form_schema($schema_form);

        // Sort by sequence_number
        $schema_form = self::sort_schema_by_sequence_number($schema_form);

        // header kolom
        $header_columns = '';
        foreach ($schema_form as $attribute_name => $attribute_value) {
            $header_columns .= sprintf('<th class="text-nowrap" scope="col">%s</th>', ucwords($attribute_name));
        }

        $action_column = '';
        if (is_role('Admin')) $action_column = '<th class="text-nowrap" scope="col">Action</th>';

        return <<<HTML
                <thead>
                    <tr>
                        <th class="text-nowrap" scope="col">No</th>
                        {$header_columns}
                        <th class="text-nowrap" scope="col">Atached File</th>
                        <th class="text-nowrap" scope="col">Created At</th>
                        <th class="text-nowrap" scope="col">Updated At</th>
                        {$action_column}
                    </tr>
                </thead>
                HTML;
    }

    /**
     * Generates the HTML for the table body (tbody) based on the provided schema.
     *
     * The function extracts fields from the schema, sorts them by their sequence number,
     * and constructs an HTML table body row for each data in the table. Each field
     * value is used as a column value. Additional columns for "No", "Atached File", "Created At", "Updated At",
     * and "Action" are appended.
     *
     * @param string $name The name of the document type.
     * @param string $table_name The name of the table.
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $data_document_type list data of document type
     * @param array $old_table_schema An array representing the schema with field definitions.
     * @param int $pagination_limit The number of records to be displayed per page.
     * @param string $action The action to be performed in browse page, either 'browse' or 'attach'.
     *
     * @return string The generated HTML for the table body.
     */
    public static function create_table_tbody_from_schema_in_html(string $name, string $table_name, \Illuminate\Contracts\Pagination\LengthAwarePaginator $data_document_type, array $old_table_schema, $action = 'browse')
    {
        // searching data
        $search = request()->search;
        $column_search = request()->column;

        // Sort by sequence_number
        $old_table_schema = self::sort_schema_by_sequence_number($old_table_schema);

        $columns = self::get_table_columns_name_from_schema_representation($table_name, $old_table_schema); // delete attribute id and file_id from list of column array

        if ($data_document_type->isNotEmpty()) {
            $no = 1;
            $rows = [];
            foreach ($data_document_type as $data) {
                $index = ($data_document_type->currentPage() - 1) * $data_document_type->perPage() + $no;

                $row_data = [];
                foreach ($columns as $column) {
                    $value = $data->$column ?? '';

                    // Highlight search keyword
                    if ($search) {
                        $value = preg_replace("/(" . preg_quote($search, '/') . ")/i", '<mark>$1</mark>', $value);
                    }

                    // set data to type DATE, TIME, or DATETIME and ignore format data if format does not match with type DATE, TIME, or DATETIME
                    switch ($old_table_schema[$column]['type']) {
                        case 'date':
                            array_push($row_data, "<td class=\"text-nowrap\">" . ($value ? "<time datetime=\"{$data->$column}\">" . str_replace($search, "<mark>$search</mark>", Carbon::parse($data->$column, 'Asia/Jakarta')->format('d F Y')) . "</time>" : '') . "</td>");
                            break;
                        case 'time':
                            array_push($row_data, "<td class=\"text-nowrap\">" . ($value ? "<time datetime=\"{$data->$column}\">" . str_replace($search, "<mark>$search</mark>", Carbon::parse($data->$column, 'Asia/Jakarta')->format('H:i A')) . "</time>" : '') . "</td>");
                            break;
                        case 'datetime':
                            array_push($row_data, "<td class=\"text-nowrap\">" . ($value ? "<time datetime=\"{$data->$column}\">" . str_replace($search, "<mark>$search</mark>", Carbon::parse($data->$column, 'Asia/Jakarta')->format('d F Y, H:i A')) . "</time>" : '') . "</td>");
                            break;
                        default:
                            array_push($row_data, "<td class=\"text-nowrap\">$value</td>");
                            break;
                    }
                }

                // add file link
                $preview_file_link = '';
                if ($data->file_id !== null) {
                    $preview_file_link = "<a href=\"" . route('documents.files.preview', [$name, 'file' => $data->file_encrypted_name]) . "\" role=\"button\" title=\"Button: to preview file {$data->file_name}.{$data->file_extension}\">" . str_replace($search, "<mark>$search</mark>", "{$data->file_name}.{$data->file_extension}") . "</a>";
                }

                if (is_role('Admin') && $action === 'browse') {
                    $buttons_action = "<a href=\"" . route('documents.data.edit', [$name, $data->id]) . "\" class=\"btn btn-warning btn-sm btn-edit\" role=\"button\" title=\"Button: to edit data of document type '$table_name'\" data-id=\"{$data->id}\"><i class=\"bi bi-pencil-square fs-5\"></i></a>
                        <a href=\"" . route('documents.data.delete', [$name, $data->id]) . "\" class=\"btn btn-danger btn-sm btn-delete\" role=\"button\" title=\"Button: to edit data of document type '$table_name'\" data-id=\"{$data->id}\" onclick=\"return confirm('Are you sure you want to delete this data?')\"><i class=\"bi bi-trash fs-5\"></i></a>";
                } else if (is_role('Admin') && $action === 'attach') {
                    $buttons_action = "<div class=\"form-check form-switch mb-3\">
                            <input class=\"form-check-input\" type=\"checkbox\" id=\"cbx-attached-data-to-file-$no\" name=\"data_id[]\" value=\"$data->id\" " . (in_array($data->id, old('data_id', [])) ? 'checked' : '') . " aria-label=\"Attaching on current data to file in request\" aria-required=\"false\">
                            <label class=\"form-check-label\" for=\"cbx-attached-data-to-file-$no\">Attach</label>
                        </div>";
                } else {
                    $buttons_action = '';
                }

                // add created_at, updated_at, button to delete and edit data of document type
                array_push($row_data, "<td class=\"text-nowrap permalink-file\">" . ($preview_file_link ?? '') . "</td>
                    <td class=\"text-nowrap\"><time datetime=\"$data->created_at\">" . str_replace($search, "<mark>$search</mark>", Carbon::parse($data->created_at, 'Asia/Jakarta')->format('d F Y, H:i A')) . "</time></td>
                    <td class=\"text-nowrap\"><time datetime=\"$data->updated_at\">" . str_replace($search, "<mark>$search</mark>",     Carbon::parse($data->updated_at, 'Asia/Jakarta')->format('d F Y, H:i A')) . "</time></td>
                    <td class=\"text-nowrap\">$buttons_action</td>"); // buttons action for admin user

                array_push($rows, sprintf(
                    '<tr aria-rowindex="%d" role="row">
                        <th scope="row" class="text-nowrap" data-id="%d">%d</th>
                        %s
                    </tr>',
                    $index,
                    $data->id,
                    $index,
                    implode("\n", $row_data)
                ));

                $no++;
            }

            $rows = implode("\n", $rows);
            return "<tbody>$rows</tbody>";
        } else {
            $message = $search
                ? "No data available for the current document type that matches '<mark>{$search}</mark>'" . ($column_search ? " in column <mark>{$column_search}</mark>" : '') .  "."
                : "No data available for current document type.";

            return "<tbody>
                        <tr aria-hidden=\"true\" aria-label=\"No data current document type\" role=\"row\" aria-rowindex=\"1\">
                            <td colspan=\"" . (count($columns) + (is_role('Admin') ? 5 : 4)) . "\" class=\"text-center\">$message</td>
                        </tr>
                    </tbody>";
        }
    }

    /**
     * Generates validation rules for a given schema.
     *
     * @param string $table_name The name of the table.
     * @param array $schema_form The schema of the form.
     * @param array $columns_name The columns name of the table.
     * @param string|int|null $update_id The ID of the document to update.
     * @return array The generated validation rules.
     * 
     * @throws \InvalidArgumentException When columns name is invalid.
     * @throws \OutOfRangeException When field type is invalid.
     */
    public static function get_validation_rules_from_schema(string $table_name, array $schema_form, array $columns_name, string|int|null $update_id = null): mixed
    {
        // Validate columns name
        $schema_columns_name = array_column($schema_form, 'name');
        if (!empty(array_diff($columns_name, $schema_columns_name))) {
            throw new \InvalidArgumentException();
        }

        $validation_rules = [];

        foreach ($schema_form as $attribute) {
            $field_name = $attribute['name'];
            $type = $attribute['type'];
            $required = $attribute['required'];
            $unique = $attribute['unique'];
            $rules_config = $attribute['rules'];

            $field_rules = [];
            $field_rules[] = $required === true ? 'required' : 'nullable';

            $type_rules = match ($type) {
                'text', 'textarea' => [
                    'string',
                    ...(isset($rules_config['maxLength']) ? ["max:{$rules_config['maxLength']}"] : []),
                    ...(isset($rules_config['minLength']) ? ["min:{$rules_config['minLength']}"] : [])
                ],
                'number' => [
                    'numeric',
                    ...(isset($rules_config['min']) ? ["min:{$rules_config['min']}"] : []),
                    ...(isset($rules_config['max']) ? ["max:{$rules_config['max']}"] : [])
                ],
                'date'       => [
                    'string',
                    'date',
                    'date_format:Y-m-d',
                    ...(isset($rules_config['minDate']) ? ["after_or_equal:{$rules_config['minDate']}"] : []),
                    ...(isset($rules_config['maxDate']) ? ["before_or_equal:{$rules_config['maxDate']}"] : [])
                ], // min date dan max date
                'time'       => [
                    'string',
                    'date_format:H:i',
                    ...(isset($rules_config['minTime']) ? ["after_or_equal:{$rules_config['minTime']}"] : []),
                    ...(isset($rules_config['maxTime']) ? ["before_or_equal:{$rules_config['maxTime']}"] : [])
                ], // min time dan max time
                'datetime'  => [
                    'string',
                    'date_format:Y-m-d H:i:s',
                    ...(isset($rules_config['minDate']) ? ["after_or_equal:{$rules_config['minDate']}"] : []),
                    ...(isset($rules_config['maxDate']) ? ["before_or_equal:{$rules_config['maxDate']}"] : [])
                ], // min date dan max date
                'email'      => [
                    'string',
                    'email',
                    ...(isset($rules_config['maxLength']) ? ["max:{$rules_config['maxLength']}"] : []),
                    ...(isset($rules_config['minLength']) ? ["min:{$rules_config['minLength']}"] : [])
                ],
                'url'        => [
                    'string',
                    'url',
                    ...(isset($rules_config['maxLength']) ? ["max:{$rules_config['maxLength']}"] : []),
                    ...(isset($rules_config['minLength']) ? ["min:{$rules_config['minLength']}"] : [])
                ],
                'phone'      => [
                    'string',
                    'regex:/^\+?[1-9]\d{0,2}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,9}[-.\s]?\d{1,' . (int) self::FIELD_TYPE_CONFIG['phone']['maxLength'] . '}$/',
                    'max:' . self::FIELD_TYPE_CONFIG['phone']['maxLength'],
                ],
                'select'     => ['in:' . implode(',', $rules_config['options'])],
                default      => throw new \OutOfRangeException("Unsupported field type: {$type}", Response::HTTP_BAD_REQUEST),
            };

            $field_rules = array_merge($field_rules, $type_rules);

            // Handle unique rule if type is not select
            if ($unique && $type !== 'select') {
                $field_rules[] = "unique:$table_name,$field_name" . ($update_id ? ",$update_id" : '');
            }

            $validation_rules[$field_name] = $field_rules;
        }

        return $validation_rules;
    }

    /**
     * Returns an array of example data for importing data into a table.
     *
     * The function takes the table name, schema form, and limit as input.
     * The schema form should contain the column names and their respective field types.
     * The function will generate example data based on the provided schema form
     * and return an array of example data.
     *
     * @param string $table_name The name of the table.
     * @param array $schema_form The schema form containing column names and their respective field types.
     * @param int $limit The number of example data to generate.
     *
     * @return array An array of example data.
     *
     * @throws \InvalidArgumentException If the table does not exist.
     * @throws \OutOfRangeException If the field type is not supported.
     */
    public static function get_example_data_for_import(string $table_name, array $schema_form, int $limit = 2): array
    {
        // check if table exists
        if (!SchemaBuilder::table_exists($table_name))
            throw new \InvalidArgumentException("Table {$table_name} does not exist.", Response::HTTP_BAD_REQUEST);

        // reordered the schema before next proccess
        $schema_form = SchemaBuilder::reorder_schema_sequence_number($schema_form);

        $faker = Faker::create();
        $columns_type = array_column($schema_form, 'type');
        $example_data = [];

        $matching_faker_type = function (string $type, array $value = []) use ($faker) { // $value is required if type is select/enum
            return match ($type) {
                'text', 'textarea' => Str::substr($faker->sentence($value['maxLength'] ?? 4), 0, $value['maxLength'] ?? 255),
                'number' => $faker->numberBetween($value['min'] ?? 0, $value['max'] ?? random_int(1, 9)),
                'date' => $faker->date(),
                'time' => $faker->time('H:i'),
                'datetime' => $faker->dateTime(),
                'email' => $faker->email(),
                'url' => $faker->url(),
                'phone' => $faker->phoneNumber(),
                'select' => $faker->randomElement($value['options'] ?? ['option 1', 'option 2', 'option 3']),
                default => throw new \OutOfRangeException("Unsupported field type: {$type}", Response::HTTP_BAD_REQUEST),
            };
        };

        for ($i = 0; $i < $limit; $i++) {
            // push new array
            $example_data[] = [];

            for ($j = 0; $j < count($columns_type); $j++) {
                $example_data[$i][array_keys($schema_form)[$j]] = $matching_faker_type($columns_type[$j], array_column($schema_form, 'rules')[$j]);
            }
        }

        return $example_data;
    }

    /**
     * Maps a column type to its corresponding MySQL data type.
     *
     * @param array $column_data The column data array.
     * @param string $column_name The column name.
     * @param string $action The action to perform (create or update).
     *
     * @return string The corresponding MySQL data type.
     *
     * @throws OutOfRangeException If the column type is not supported.
     */
    public static function get_attribute_type(array $column_data, string $column_name)
    {
        return match ($column_data['type']) {
            'bigint' => "VARCHAR({$column_data['maxLength']})",
            'varchar' => $column_data['maxLength'] > 255 ? "TEXT" : "VARCHAR({$column_data['maxLength']})",
            'enum' => call_user_func(function () use ($column_data) {
                $max_length = max(array_map('strlen', $column_data['enumValues']));

                return "VARCHAR({$max_length})";
            }),
            'text' => 'TEXT',
            'timestamp' => 'VARCHAR(19)',
            'date' => 'VARCHAR(11)',
            'time' => 'VARCHAR(6)',
            'datetime' => 'VARCHAR(19)',
            default => throw new \OutOfRangeException(
                sprintf(
                    "Unsupported column type '%s' for column '%s'. Supported types: bigint, varchar, enum, text, timestamp, date, time, or datetime.",
                    $column_data['type'],
                    $column_name
                ),
                Response::HTTP_BAD_REQUEST
            ),
        };
    }

    /**
     * Create a new table dynamically.
     *
     * @param string $table_name The name of the table to create.
     * @param array $new_table_schema The schema of the table to create.
     * @param array $new_form_schema The schema of the form to validate.
     * @return string
     * 
     * @throws \InvalidArgumentException when columns is empty
     * @throws \RuntimeException when table already exists in database or failed to create table
     */
    public static function create_table(string $table_name, array $new_table_schema, array $new_form_schema): string
    {
        // check if table name is not empty
        if (empty($table_name))
            throw new \InvalidArgumentException(
                "Empty table name provided. Please provide a valid table name.",
                Response::HTTP_BAD_REQUEST
            );

        // check if columns is empty
        if (empty($new_table_schema) || empty($new_form_schema))
            throw new \InvalidArgumentException(
                sprintf(
                    "Empty columns array provided for table '%s'. At least one column definition is required.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // Validate schema table structure
        self::validate_structure_of_table_schema($new_table_schema);

        // validate same name or duplicate name of attribute
        self::validate_unique_name_for_attribute(array_keys($new_table_schema));

        // Validate schema form structure
        self::validate_structure_of_form_schema($new_form_schema);

        // validate same name or duplicate name of attribute
        self::validate_unique_name_for_attribute(array_column($new_form_schema, 'name'));

        $table_name = Str::of($table_name)->lower()->snake()->trim()->toString();

        // Validate table name
        self::validate_attribute_name($table_name);

        if (self::table_exists($table_name))
            throw new \RuntimeException(
                sprintf(
                    "Table '%s' already exists in database. Choose a different name.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        try {
            // Create table using raw SQL
            $columns_sql = [];

            // Add id column
            $columns_sql[] = "id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY";
            $columns_sql[] = "file_id BIGINT(20) UNSIGNED DEFAULT NULL";

            foreach ($new_table_schema as $key => $column) {
                $columns_sql[] = "$key " . self::get_column_definition($column, $key);
            }

            // Add created_at and updated_at columns
            $columns_sql[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $columns_sql[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

            // Add extra attribute
            $columns_sql = implode(', ', $columns_sql);

            DB::statement("CREATE TABLE $table_name ($columns_sql)");

            // Check if the table creation was successful
            if (!self::table_exists($table_name))
                throw new \RuntimeException(
                    sprintf(
                        "Failed to create table '%s'. Table does not exist after creation attempt. Check database permissions and configuration.",
                        $table_name
                    ),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );

            return $table_name;
        } catch (\Exception $e) {
            try {
                if (SchemaBuilder::table_exists($table_name)) SchemaBuilder::drop_table($table_name);
            } catch (\Exception $e) {
                Log::error(
                    sprintf(
                        "System error: failed to rollback creation of table '%s' after failed table creation. Error: %s",
                        $table_name,
                        $e->getMessage()
                    )
                );

                throw new \RuntimeException(
                    sprintf(
                        "System error: failed to rollback creation of table '%s' after failed table creation. Error: %s",
                        $table_name,
                        $e->getMessage()
                    ),
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    $e
                );
            }

            throw new \RuntimeException(
                sprintf(
                    "Failed to create table '%s'. Error: %s",
                    $table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Checks if a table with the given name exists in the database.
     *
     * @param string $table_name
     * @return bool
     */
    public static function table_exists(string $table_name): bool
    {
        return (bool) DB::select("SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table_name}'");
    }

    /**
     * Drop a table dynamically.
     *
     * @param string $table_name The name of the table to drop.
     * @return bool
     */
    public static function drop_table(string $table_name): bool
    {
        return DB::statement("DROP TABLE `{$table_name}`");
    }

    /**
     * Checks if an index with the given name exists in the given table.
     *
     * @param string $table_name The name of the table to check for index existence.
     * @param string $index_name The name of the index to check for existence.
     *
     * @return bool True if the index exists, false otherwise.
     */
    public static function index_exists(string $table_name, string $index_name): bool
    {
        return (bool) DB::select("SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table_name}' AND INDEX_NAME = '{$index_name}'");
    }

    /**
     * Drop an index from a table dynamically.
     *
     * @param string $table_name The name of the table to drop the index from.
     * @param string $index_name The name of the index to drop.
     *
     * @return bool True if the index was dropped successfully, false otherwise.
     */
    public static function drop_index(string $table_name, string $index_name): bool
    {
        return DB::statement("ALTER TABLE `{$table_name}` DROP INDEX `{$index_name}`");
    }

    public static function create_index(string $table_name, string $column_name, string $index_name): bool
    {
        return DB::statement("ALTER TABLE `{$table_name}` ADD INDEX `{$index_name}` (`{$column_name}`)");
    }

    /**
     * Validate unique name for attribute.
     *
     * This function validates if column name that will be added already exists in the table.
     *
     * @param array $columns_name The name's attribute of the schema representation for column addition.
     * @param string|null $table_name The name of the table to check for duplicate column names.
     *
     * @throws \RuntimeException If a column name already exists in the table.
     */
    public static function validate_unique_name_for_attribute(array $columns_name, ?string $table_name = null): void
    {
        $restricted_names = ['id', 'file_id', 'file id', 'created_at', 'created at', 'updated_at', 'updated at'];

        $columns_name = array_map('strtolower', $columns_name);

        $columns_name = array_merge($columns_name, array_filter(array_map(function ($column_name) {
            if (strpos($column_name, '_')) {
                return str_replace('_', ' ', $column_name);
            } else {
                return null;
            }
        }, $columns_name), fn($column) => $column !== null, ARRAY_FILTER_USE_BOTH));

        // check duplicate column name
        if (empty($table_name)) {
            $duplicate_columns_name = array_keys(array_filter(array_count_values($columns_name), fn($count) => $count > 1, ARRAY_FILTER_USE_BOTH));
        } else {
            $old_columns_name = array_map('strtolower', self::get_form_columns_name_from_schema_representation($table_name));
            $duplicate_columns_name = array_keys(array_filter(array_count_values(array_merge($columns_name, $old_columns_name)), fn($count) => $count > 1, ARRAY_FILTER_USE_BOTH));
        }

        // Check duplicate column name and contains restricted names
        $invalid_columns = array_intersect($columns_name, $restricted_names);

        if (!empty($duplicate_columns_name)) {
            throw new \RuntimeException(
                sprintf(
                    "Duplicate column names found in new columns: '%s'. Choose unique names.",
                    implode("', '", $duplicate_columns_name)
                )
            );
        }

        if (!empty($invalid_columns)) {
            throw new \RuntimeException(
                sprintf(
                    "Column names '%s' are not allowed as new columns. Choose different names.",
                    implode("', '", $invalid_columns)
                )
            );
        }
    }

    /**
     * Get one of the longest data in a column.
     *
     * @param string $table_name The name of the table.
     * @param string $column_name The name of the column.
     * @return string The longest data in the column, or null if data is not found or empty.
     */
    public static function get_longest_data_in_column(string $table_name, string $column_name): ?int
    {
        $result = DB::table($table_name)
            ->selectRaw('MAX(LENGTH(`' . $column_name . '`)) AS Max_Length_String')
            ->value('Max_Length_String');

        return $result ?: null;
    }

    /**
     * Update a table dynamically.
     *
     * @param string $table_name The name of the table to update.
     * @param array $old_form_schema The schema representation of the form before the table update.
     * @param array $old_table_schema The schema representation of the table before the table update.
     * @param array $new_form_schema The schema representation of the form after the table update.
     * @param array $new_table_schema The schema representation of the table after the table update.
     * @return void
     * 
     * @throws \InvalidArgumentException when columns is empty
     * @throws \RuntimeException when table does not exist or fails to update table
     */
    public static function update_table(string $table_name, array $old_form_schema, array $old_table_schema, array $new_form_schema, array $new_table_schema)
    {
        // check if table name is not empty
        if (empty($table_name))
            throw new \InvalidArgumentException(
                sprintf(
                    "Empty table name provided. Please provide a valid table name.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // check if columns is empty
        if (empty($new_table_schema) || empty($old_table_schema) || empty($new_form_schema) || empty($old_form_schema))
            throw new \InvalidArgumentException(
                sprintf(
                    "Empty columns array provided for table update '%s'. At least one column definition is required.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // Validate schema table structure
        self::validate_structure_of_table_schema($new_table_schema, 'update');
        self::validate_structure_of_table_schema($old_table_schema);

        // validate same name or duplicate name of attribute 
        self::validate_unique_name_for_attribute(array_keys($new_table_schema));

        // Validate schema form structure
        self::validate_structure_of_form_schema($new_form_schema, 'update');
        self::validate_structure_of_form_schema($old_form_schema);

        // validate same name or duplicate name of attribute 
        self::validate_unique_name_for_attribute(array_column($new_form_schema, 'name'));

        if (!self::table_exists($table_name))
            throw new \RuntimeException(
                sprintf(
                    "Table '%s' does not exist in database. Create the table first.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // check if columns in database is exist in old columns table
        foreach ($old_table_schema as $column => $value) {
            if (!self::column_exists($table_name, $column))
                throw new \RuntimeException(
                    sprintf(
                        "Column '%s' not found in table '%s'. Column may have been deleted or database structure modified externally or corrupted.",
                        $column,
                        $table_name
                    ),
                    Response::HTTP_BAD_REQUEST
                );
        }

        /**
         * Checks if the 'id' values in the new columns array exist in the old columns array.
         * If not, it throws an exception.
         *
         * @param array $new_columns
         * @param array $old_columns
         * @param string $table_name
         * @throws \InvalidArgumentException if the 'id' value is not found in the old columns array
         */
        function check_id(array $new_columns, array $old_columns, string $table_name)
        {
            foreach ($new_columns as $new_field => $new_value) {
                $id_found = false;

                foreach ($old_columns as $old_value) {
                    if ($new_value['id'] === $old_value['id']) {
                        $id_found = true;
                        break;
                    }
                }

                // Validate IDs match between old and new columns
                if ($id_found === false) {
                    // log error when id is not found or may be corrupted
                    Log::error(
                        sprintf(
                            'Column ID mismatch. ID "%s" in new column "%s" not found in existing table "%s".',
                            $new_value['id'],
                            $new_field,
                            $table_name
                        )
                    );

                    throw new \InvalidArgumentException(
                        sprintf(
                            "Column ID mismatch. ID '%s' in new column '%s' not found in existing table '%s'.",
                            $new_value['id'],
                            $new_field,
                            $table_name
                        ),
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        // Check if attribute 'id' in $new_table_schema exists in $old_table_schema
        check_id($new_table_schema, $old_table_schema, $table_name);
        // check if attribute 'id' in $new_form_schema exist in $old_form_schema
        check_id($new_form_schema, $old_form_schema, $table_name);

        try {
            // update form and table schema
            list($new_schema_form, $new_schema_table) = [
                self::update_schema_for_table_and_form($old_form_schema, $new_form_schema, $table_name),
                self::update_schema_for_table_and_form($old_table_schema, $new_table_schema, $table_name)
            ];

            // update table columns
            self::update_columns($table_name, $old_table_schema, $new_table_schema);

            // Return the updated table and form
            return [
                'table_name' => $table_name,
                'table' => $new_schema_table,
                'form' => $new_schema_form
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf(
                    "Failed to update table '%s'. Error: %s",
                    $table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Rename a table dynamically.
     *
     * @param string $table_name The original table name
     * @param string $new_table_name The new table name
     * @return void
     * 
     * @throws \RuntimeException when table does not exist or fails to rename table
     */
    public static function rename_table(string $table_name, string $new_table_name): bool
    {
        // Validate table name
        self::validate_attribute_name($new_table_name);

        try {
            if (!self::table_exists($table_name) && self::table_exists($new_table_name))
                throw new \RuntimeException(
                    sprintf(
                        "Table '%s' already exists in database. Choose a different name.",
                        $new_table_name
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            return DB::statement("RENAME TABLE `{$table_name}` TO `{$new_table_name}`");
        } catch (\Exception $e) {
            Log::error(
                sprintf(
                    "System error: failed to rename table '%s' to '%s'. Error: %s",
                    $table_name,
                    $new_table_name,
                    $e->getMessage()
                )
            );

            throw new \RuntimeException(
                sprintf(
                    "Failed to rename table '%s' to '%s'. Error: %s",
                    $table_name,
                    $new_table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $table_name The name of the table.
     * @param string $column_name The name of the column to check for existence.
     * @return bool True if the column exists, false otherwise.
     */
    public static function column_exists(string $table_name, string $column_name): bool
    {
        return (bool) DB::select("SHOW COLUMNS FROM `{$table_name}` LIKE '{$column_name}'");
    }

    /**
     * Returns an array of the columns in a table.
     *
     * @param string $table_name The name of the table to retrieve the columns from.
     * @return array An array of column names in the table.
     */
    public static function get_table_columns(string $table_name): array
    {
        return DB::select("SHOW COLUMNS FROM `{$table_name}`");
    }

    /**
     * Returns an array of columns name in a table excluding 'id', 'file_id', 'created_at', and 'updated_at'.
     * 
     * This function is intended for schema table operations.
     *
     * @param string $table_name The name of the table to retrieve the columns from.
     * @param ?array $old_table_schema The schema representation of the table.
     * @param bool $including If true, include 'id', 'file_id', 'created_at', and 'updated_at' in the result and false otherwise.
     * @return array An array of column names in the table.
     */
    public static function get_table_columns_name_from_schema_representation(string $table_name, ?array $old_table_schema = null, bool $including = false): array
    {
        $schema_columns = array_values(array_column(self::get_table_columns($table_name), 'Field'));
        $schema_columns = array_diff($schema_columns, ['id', 'file_id', 'created_at', 'updated_at']);

        // reindex from index 0 
        $schema_columns = array_values($schema_columns);

        if ($old_table_schema !== null) {
            // Sort by sequence_number
            $old_table_schema = self::sort_schema_by_sequence_number($old_table_schema);

            $desired_order = array_keys($old_table_schema);

            // reorder the schema columns
            usort($schema_columns, function ($a, $b) use ($desired_order) {
                $posA = array_search($a, $desired_order);
                $posB = array_search($b, $desired_order);

                $posA = ($posA !== false) ? $posA : PHP_INT_MAX;
                $posB = ($posB !== false) ? $posB : PHP_INT_MAX;

                return $posA - $posB;
            });
        }

        if ($including === true) {
            array_unshift($schema_columns, 'id', 'file_id');
            array_push($schema_columns, 'created_at', 'updated_at');
        }

        return $schema_columns;
    }

    /**
     * Returns an array of columns name in a table excluding 'id', 'file_id', 'created_at', and 'updated_at' that is used for form operations.
     * 
     * This function is intended for schema form operations.
     *
     * @param string $table_name The name of the table to retrieve the columns from.
     * @param ?array $old_form_schema The schema representation of the form.
     * @param bool $including If true, include 'id', 'file_id', 'created_at', and 'updated_at' in the result and false otherwise.
     * @return array An array of column names in the table.
     */
    public static function get_form_columns_name_from_schema_representation(string $table_name, ?array $old_form_schema = null, bool $including = false): array
    {
        $schema_columns = array_values(array_column(self::get_table_columns($table_name), 'Field'));
        $schema_columns = array_diff($schema_columns, ['id', 'file_id', 'created_at', 'updated_at']);

        // reindex from index 0 
        $schema_columns = array_values($schema_columns);

        if ($old_form_schema !== null) {
            // Sort by sequence_number
            $old_form_schema = self::sort_schema_by_sequence_number($old_form_schema);

            $desired_order = array_column($old_form_schema, 'name');

            // reorder the schema columns
            usort($schema_columns, function ($a, $b) use ($desired_order) {
                $posA = array_search($a, $desired_order, true);
                $posB = array_search($b, $desired_order, true);

                $posA = ($posA !== false) ? $posA : PHP_INT_MAX;
                $posB = ($posB !== false) ? $posB : PHP_INT_MAX;

                return $posA <=> $posB;
            });
        }

        if ($including === true) {
            array_unshift($schema_columns, 'id', 'file_id');
            array_push($schema_columns, 'created_at', 'updated_at');
        }

        return $schema_columns;
    }

    /**
     * Retrieves the checksum value for a specified table.
     *
     * This function queries the database to calculate and return the checksum value 
     * for the given table. The checksum can be used to detect changes to the table 
     * since the last checksum calculation.
     *
     * @param string $table The name of the table to calculate the checksum for.
     * @return string The checksum value of the specified table.
     */
    private static function get_table_checksum(string $table): string
    {
        return DB::selectOne("CHECKSUM TABLE `{$table}`")->Checksum;
    }

    /**
     * Adds a column to a table.
     *
     * @param string $table_name The name of the table to add the column to.
     * @param array $old_table_schema The schema representation of the table before the column addition.
     * @param array $new_table_schema The schema representation of the table after the column addition.
     *
     * @throws \InvalidArgumentException If the column configuration is invalid.
     * @throws \RuntimeException If the table does not exist in the database or if the column already exists in the table or fails to add the column.
     */
    public static function add_column(string $table_name, array $old_table_schema, array $new_table_schema)
    {
        // dd($table_name, $old_table_schema, $new_table_schema, empty($new_table_schema));

        // check if table nama is not empty
        if (empty($table_name))
            throw new \InvalidArgumentException(
                sprintf(
                    "Empty table name provided. Please provide a valid table name.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // check if columns is empty
        if (empty($new_table_schema))
            throw new \InvalidArgumentException(
                sprintf(
                    "Empty columns array provided for table '%s'. At least one column definition is required.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        // Validate schema table structure
        self::validate_structure_of_table_schema($new_table_schema);

        if (!empty($old_table_schema))
            self::validate_structure_of_table_schema($old_table_schema);

        // validate same name or duplicate name of attribute
        self::validate_unique_name_for_attribute(array_keys($new_table_schema), $table_name);

        if (!self::table_exists($table_name))
            throw new \RuntimeException(
                sprintf(
                    "Table '%s' does not exist in database. Please make sure the table exists and try again.",
                    $table_name
                ),
                Response::HTTP_BAD_REQUEST
            );

        try {
            $rollback_actions = [];
            $existing_columns = self::get_table_columns_name_from_schema_representation($table_name, $old_table_schema, true);

            $first_placement = "";
            if (($pos = array_search('created_at', $existing_columns)) !== false) {
                if ($pos === 0) {
                    $first_placement = "FIRST";
                } else {
                    $first_placement = "AFTER " . $existing_columns[$pos - 1];
                }
            }

            // insert new columns using raw SQL
            $columns_sql = [];
            $last_added_column = null;

            foreach ($new_table_schema as $key => $column) {
                $placement = "";
                if ($first_placement !== "") {
                    if ($last_added_column === null) {
                        $placement = $first_placement;
                    } else {
                        $placement = "AFTER " . $last_added_column;
                    }
                }
                $last_added_column = $key;

                $columns_sql[] = trim("ADD COLUMN `$key` " . self::get_column_definition($column, $key) . " $placement");

                $rollback_actions[] = trim("DROP COLUMN IF EXISTS `$key`");
            }

            DB::statement("ALTER TABLE `$table_name` " . implode(', ', $columns_sql));
        } catch (\Exception $e) {
            try {
                foreach ($rollback_actions as $action) {
                    DB::statement("ALTER TABLE `$table_name` $action");
                }
            } catch (\Exception $e) {
                Log::error(
                    sprintf(
                        "System error: failed to rollback column addition for table %s. Error: %s",
                        $table_name,
                        $e->getMessage()
                    )
                );

                throw new \RuntimeException(
                    sprintf(
                        "Failed to rollback column addition for table '%s'. Error: %s",
                        $table_name,
                        $e->getMessage()
                    ),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            throw new \RuntimeException(
                sprintf(
                    "Failed to add new column to table '%s'. Error: %s",
                    $table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Gets the column names from a schema given an attribute id.
     *
     * @param array $table_schema The old table schema.
     * @param array|string $attribute_id The attribute id to filter by.
     * @return array The column names.
     */
    public static function get_columns_name_from_schema(array $table_schema, array|string $attribute_id)
    {
        $column_names = array_keys(SchemaBuilder::filter_schema_attributes_of_document_type($table_schema, $attribute_id));

        return $column_names;
    }

    /**
     * Drops a column from a table.
     *
     * This function takes a table name and the schema definitions for the table and form,
     * as well as a data ID or array of data IDs to drop. It checks if the columns exist
     * in the table and if not, throws an exception. It then drops the columns from the
     * table and updates the table and form schema definitions accordingly.
     *
     * @param string $table_name The name of the table to drop the columns from.
     * @param array $old_table_schema The existing table schema definitions.
     * @param array $old_form_schema The existing form schema definitions.
     * @param array|string $attribute_id The data ID or array of data IDs to drop.
     *
     * @return array An associative array containing the updated table schema definitions
     *               and form schema definitions.
     * 
     * @throws \RuntimeException If the columns do not exist in the table, or if there
     *                            is a system error when dropping the columns.
     */
    public static function drop_column(string $table_name, array $old_table_schema, array $old_form_schema, array|string $attribute_id): array
    {
        try {
            $columns_name = array_intersect(self::get_table_columns_name_from_schema_representation($table_name, $old_table_schema), self::get_columns_name_from_schema($old_table_schema, $attribute_id));

            if (empty($columns_name)) {
                throw new \RuntimeException(
                    sprintf(
                        "Columns '%s' do not exist in table '%s'.",
                        implode("', '", $columns_name),
                        $table_name
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (DB::statement("ALTER TABLE `{$table_name}` " . implode(',', array_map(function ($column_name) {
                return "DROP COLUMN `{$column_name}`";
            }, $columns_name)))) {
                list($new_schema_table, $new_schema_form) = [
                    self::drop_schema_for_table_and_form($old_table_schema, self::filter_schema_attributes_of_document_type($old_table_schema, $attribute_id), $table_name, $attribute_id),
                    self::drop_schema_for_table_and_form($old_form_schema, self::filter_schema_attributes_of_document_type($old_form_schema, $attribute_id), $table_name, $attribute_id)
                ];

                return [
                    'table_name' => $table_name,
                    'table' => $new_schema_table,
                    'form' => $new_schema_form,
                ];
            } else {
                throw new \RuntimeException(
                    sprintf(
                        "Could not drop columns '%s' from table '%s', system error.",
                        implode("', '", $columns_name),
                        $table_name
                    ),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (\Exception $e) {
            Log::error(
                sprintf(
                    "System error: failed to drop columns '%s' from table '%s'. Error: %s",
                    implode("', '", $columns_name),
                    $table_name,
                )
            );

            throw new \RuntimeException(
                sprintf(
                    "Failed to drop columns from table '%s'. Error: %s",
                    $table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Renames a column in a table.
     *
     * @param string $table_name The name of the table to rename the column from.
     * @param string $column_name The name of the column to rename.
     * @param string $new_column_name The new name of the column.
     */
    public static function rename_column(string $table_name, string $column_name, string $new_column_name)
    {
        try {
            if (!self::column_exists($table_name, $column_name))
                throw new \RuntimeException(
                    sprintf(
                        "Column '%s' does not exist in table '%s'.",
                        $column_name,
                        $table_name
                    ),
                    Response::HTTP_BAD_REQUEST
                );

            return DB::statement("ALTER TABLE `{$table_name}` CHANGE `{$column_name}` `{$new_column_name}`");
        } catch (\Exception $e) {
            Log::error(
                sprintf(
                    "System error: failed to rename column '%s' to '%s' in table '%s'. Error: %s",
                    $column_name,
                    $new_column_name,
                    $table_name,
                    $e->getMessage()
                )
            );

            throw new \RuntimeException(
                sprintf(
                    "Failed to rename column '%s' to '%s' in table '%s'. Error: %s",
                    $column_name,
                    $new_column_name,
                    $table_name,
                    $e->getMessage()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Gets the columns in a table that an index uses.
     *
     * @param string $table_name The name of the table to get the index columns from.
     * @param string $index_name The name of the index to get the columns from.
     * @return array Array of column names.
     */
    public static function get_index_columns(string $table_name, string $index_name): array
    {
        return DB::select(
            "SELECT COLUMN_NAME 
             FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = ? 
             AND INDEX_NAME = ?",
            [$table_name, $index_name]
        );
    }


    /**
     * Filters a schema array based on the given attribute IDs.
     *
     * This function takes two parameters, an array of schema attributes and
     * an array or a single value of attribute IDs. It then filters the given
     * schema array based on the attribute IDs, and returns the filtered schema
     * array. If the schema array or the attribute IDs are empty, the function
     * returns null.
     *
     * @param array $schema The schema array to filter.
     * @param array|string|int $attributes_id The attribute IDs to filter the
     *                                        schema array by.
     * @return array The filtered schema array, or null if the schema array
     *                    or the attribute IDs are empty.
     */
    public static function filter_schema_attributes_of_document_type(array $schema, array|string|int $attributes_id)
    {
        // check if attribute id and schema is empty
        if (empty($schema)) return null;

        $schema = array_filter($schema, function ($item) use ($attributes_id) {
            if (is_array($attributes_id)) {
                return isset($item['id']) && in_array($item['id'], $attributes_id);
            }

            return isset($item['id']) && $item['id'] == $attributes_id;
        }, ARRAY_FILTER_USE_BOTH);

        // check if schema form is empty
        if (empty($schema)) return null;

        return $schema;
    }


    /**
     * Update columns in a table.
     *
     * This function takes three parameters, the name of the table to update,
     * an array of old columns, and an array of new columns. The old columns
     * are expected to have an 'id' key, and the new columns are expected to
     * have the same 'id' key in order to match the old columns to the new
     * ones. The function then identifies which columns need to be updated,
     * and applies the necessary changes in the following order:
     * 1. Drops any unique indexes that need to be dropped.
     * 2. Applies the column changes.
     * 3. Creates any unique indexes that need to be created.
     *
     * @param string $table_name The name of the table to update.
     * @param array $old_table_schema The old columns in the table. The array
     *     should have the column name as the key and the column metadata
     *     as the value. The metadata should include an 'id' key.
     * @param array $new_table_schema The new columns in the table. The array
     *     should have the column name as the key and the column metadata
     *     as the value. The metadata should include an 'id' key.
     *
     * @throws \RuntimeException When there is a system error during the update.
     */
    public static function update_columns(string $table_name, array $old_table_schema, array $new_table_schema)
    {
        $new_table_schema_map = array_column($new_table_schema, null, 'id');
        $updates = [];

        // Identify required changes
        foreach ($old_table_schema as $old_column_name => $old_column_value) {
            $new_column_value = $new_table_schema_map[$old_column_value['id']] ?? null;

            if (empty($new_column_value)) continue;

            $new_column_name = array_key_first(
                array_filter(
                    $new_table_schema,
                    function ($n) use ($old_column_value) {
                        return $n['id'] === $old_column_value['id'];
                    }
                )
            );
            $metadata_changes = self::detect_schema_metadata_changes($old_column_value, $new_column_value);
            $name_changed = $old_column_name !== $new_column_name;

            if ($name_changed || $metadata_changes) {
                $updates[] = [
                    'old_column_name' => $old_column_name,
                    'new_column_name' => $new_column_name,
                    'column_definition' => self::get_column_definition($new_column_value, $new_column_name, 'update', $table_name, $old_column_name),
                    'original_column_definition' => self::get_column_definition($old_column_value, $old_column_name),
                ];
            }
        }

        if (empty($updates)) {
            throw new \Exception('No updates needed.', Response::HTTP_NOT_MODIFIED);
        }

        foreach ($updates as $update) {
            self::apply_column_update($table_name, $update);
        }
    }

    /**
     * Checks if any schema metadata changes are needed between two column definitions.
     *
     * This function compares the two column definitions and returns true if any
     * metadata fields have changed (type, maxLength, nullable, unique, default).
     * It is used to optimize column updates by only applying changes when
     * actual changes are needed.
     *
     * @param array $old_column The old column definition.
     * @param array $new_column The new column definition.
     * @return bool Whether any metadata changes are needed.
     */
    private static function detect_schema_metadata_changes(array $old_column, array $new_column): bool
    {
        $fields = ['type', 'maxLength', 'nullable', 'unique', 'default', 'enumValues'];
        foreach ($fields as $field) {
            if (Arr::has($old_column, $field) && Arr::has($new_column, $field)) {
                if ($old_column[$field] !== $new_column[$field]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Applies a column update on a table.
     *
     * This function takes three parameters, a table name, an array with the old and new column names and definitions, and an array with unique index operations.
     * If any of the operations fail, it throws an exception.
     *
     * @param string $table_name The name of the table to update.
     * @param array $update_data An array with the old and new column names and definitions.
     * @return bool
     * 
     * @throws \RuntimeException when table does not exist or fails to update table
     */
    private static function apply_column_update(string $table_name, array $update_data): bool
    {
        try {
            $rollback_actions = [];

            // Apply column change
            $old_column_name = $update_data['old_column_name'];
            $new_column_name = $update_data['new_column_name'];
            $column_definition = $update_data['column_definition'];
            $original_column_definition = $update_data['original_column_definition'];

            if (!self::column_exists($table_name, $old_column_name)) {
                throw new \RuntimeException(
                    sprintf(
                        "Column '%s' does not exist in table '%s'.",
                        $old_column_name,
                        $table_name
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Add rollback action
            $rollback_actions[] = [
                'type' => 'change_column',
                'old_column_name' => $new_column_name,
                'new_column_name' => $old_column_name,
                'column_definition' => $original_column_definition
            ];

            return DB::statement("
                ALTER TABLE `{$table_name}` 
                CHANGE COLUMN `{$old_column_name}` `{$new_column_name}` {$column_definition}
            ");
        } catch (\Exception $e) {
            try {
                // Rollback changes if error occurs during update
                foreach (array_reverse($rollback_actions) as $action) {
                    if ($action['type'] === 'change_column') {
                        DB::statement("ALTER TABLE `{$table_name}` CHANGE COLUMN `{$action['old_column_name']}` `{$action['new_column_name']}` {$action['column_definition']}");
                    }
                }
            } catch (\Exception $e) {
                // log error
                Log::error(
                    sprintf(
                        "System error: failed to roll back changes columns to table '%s'. Error: %s",
                        $table_name,
                        $e->getMessage()
                    )
                );

                throw new \RuntimeException(
                    sprintf(
                        "System error: failed to roll back changes to table '%s'.",
                        $table_name
                    ),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Given a column data array, returns a column definition string that can be used in a raw SQL query.
     *
     * @param array $column The column data array.
     * @param string $column_name The name of the column.
     * @param string $action The action to perform (create or update).
     * @param ?string $table_name The name of the table if the action is update.
     * @param ?string $original_column_name The original name of the column if the action is update.
     * @return string
     * 
     * @throws \InvalidArgumentException when column type is enum and enumValues is not an array
     */
    private static function get_column_definition(array $column, string $column_name, string $action = 'create', ?string $table_name = null, ?string $original_column_name = null): string
    {
        if ($column['type'] === 'enum') {
            if (!Arr::has($column, 'enumValues')) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Select column '%s' must have value.",
                        $column_name
                    )
                );
            }

            if (is_array($column['enumValues'])) {
                // check if default value include in enumValues
                if (!empty($column['default'])) {
                    if (!in_array($column['default'], $column['enumValues'])) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                "Default value for required select column '%s' is not valid. Default value must be at least one of the following: %s.",
                                $column_name,
                                implode(', ', $column['enumValues'])
                            )
                        );
                    }
                }
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Invalid select values for column '%s'.",
                        $column_name
                    )
                );
            }
        }

        $type = SchemaBuilder::get_attribute_type($column, $column_name, $action);

        if ($action === 'update') {
            $column_minimum_length_required = self::get_longest_data_in_column($table_name, $original_column_name);

            if ($column_minimum_length_required !== null && is_numeric($column_minimum_length_required)) {
                if (strpos($type, 'VARCHAR') !== false && $column['maxLength'] < $column_minimum_length_required) {
                    $type = "VARCHAR($column_minimum_length_required)";
                }
            }
        }

        $nullable = $column['nullable'] ? 'NULL' : 'NOT NULL';

        $default = !empty($column['default']) ? 'DEFAULT ' . DB::getPdo()->quote($column['default']) : '';

        // unique attribute for enum column 
        if ($column['type'] === 'enum' && $column['unique'] === true) {
            $column['unique'] = false;
        }

        if ($column['attribute'] == 'unsigned') {
            $type .= ' UNSIGNED';
        }

        return Str::of("$type $nullable $default")->trim()->toString();
    }

    /**
     * Update an array of column definitions with new values.
     *
     * This function takes an array of old column definitions and an array of new column definitions.
     * It loops through the new column definitions and checks if the 'id' attribute of the new column
     * exists in the old column definitions. If it does, it updates the old column definition with the
     * new values, except for the 'id', 'sequence_number', and 'created_at' fields. If the 'id' does not
     * exist, it throws an exception.
     *
     * @param array $old_data An array containing the existing table schema definitions.
     * @param array $new_data An array containing the new table schema definitions.
     * @param string $table_name The name of the table.
     * @return array The updated array of column definitions.
     * 
     * @throws \InvalidArgumentException When the 'id' attribute of a new column does not exist in the old column definitions.
     */
    public static function update_schema_for_table_and_form(array $old_data, array $new_data, string $table_name)
    {
        $result = $old_data;

        $existingIds = array_column($old_data, 'id');

        foreach ($new_data as $newKey => $newItem) {
            if (!Arr::has($newItem, 'id')) {
                throw new \InvalidArgumentException(
                    sprintf("Column %s does not have an field 'id'.", $newKey),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $id = $newItem['id'];
            if (!in_array($id, $existingIds)) {
                throw new \InvalidArgumentException(
                    sprintf("Column %s does not exist in the table '%s'.", $newKey, $table_name),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $oldKey = null;
            foreach ($old_data as $key => $item) {
                if ($item['id'] === $id) {
                    $oldKey = $key;
                    break;
                }
            }

            if ($oldKey !== null) {
                $oldItem = $result[$oldKey];

                // Update all fields except 'id', 'sequence_number', and 'created_at'
                $updatedItem = $oldItem;
                foreach ($newItem as $field => $value) {
                    if (!in_array($field, ['id', 'sequence_number', 'created_at'])) {
                        $updatedItem[$field] = $value;
                    }
                }

                if ($updatedItem['type'] === 'enum') {
                    if (!Arr::has($newItem, 'enumValues')) {
                        throw new \InvalidArgumentException(
                            sprintf("Column %s does not have an field 'enumValues'. 'enumValues' is required when type column is 'enum'.", $newKey),
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    $updatedItem['enumValues'] = $newItem['enumValues'];
                } else {
                    unset($updatedItem['enumValues']);
                }

                if ($newKey !== $oldKey) {
                    unset($result[$oldKey]);
                }

                $result[$newKey] = $updatedItem;
            }
        }

        return $result;
    }

    /**
     * Drops schema entries for both table and form based on specified data IDs.
     *
     * This function removes entries from the table schema and form schema
     * that correspond to the given data IDs to be dropped. It ensures that 
     * both schemas are updated consistently by removing any related columns 
     * or attributes. The function supports both single and multiple data ID 
     * inputs.
     *
     * @param array $old_data An array containing the existing table schema definitions.
     * @param array $new_data An array containing the existing form schema definitions.
     * @param string $table_name The name of the table from which schema entries will be dropped.
     * @param mixed $attribute_id Single or multiple data IDs for the schema entries to be removed.
     * @param array|string $data_id_to_drop Single or multiple data IDs for the schema entries to be removed.
     *
     * @return void
     * @throws \InvalidArgumentException If the specified data IDs do not exist in the schemas.
     */
    public static function drop_schema_for_table_and_form(array $old_data, array $new_data, string $table_name, array|string $attribute_id)
    {
        if (is_string($attribute_id)) {
            $attribute_id = [$attribute_id];
        }

        $result = $old_data;

        foreach ($attribute_id as $id) {
            $found = false;
            foreach ($old_data as $key => $item) {
                if ($item['id'] === $id) {
                    $found = true;
                    unset($result[$key]);
                    break;
                }
            }

            if (!$found) {
                throw new \InvalidArgumentException(
                    sprintf("Column with ID '%s' does not exist in the schema definition of table '%s'.", $id, $table_name),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        foreach ($attribute_id as $id) {
            $found = false;
            foreach ($new_data as $key => $item) {
                if ($item['id'] === $id) {
                    $found = true;
                    unset($new_data[$key]);
                    break;
                }
            }

            if (!$found) {
                throw new \InvalidArgumentException(
                    sprintf("Column with ID '%s' does not exist in the form schema definition of table '%s'.", $id, $table_name),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        return $result;
    }

    /**
     * Continue the sequence number of the schema.
     *
     * This function will continue the sequence number of the schema from the previous schema.
     * If the sequence number is not specified in the new schema, it will be set to the last sequence number from the previous schema.
     *
     * @param array $old_schema The previous schema.
     * @param array $new_schema The new schema.
     *
     * @return array The new schema with the continued sequence number.
     */
    public static function continues_schema_sequence_number(array $old_schema, array $new_schema)
    {
        $previous_sequence_number = array_column($old_schema, 'sequence_number');
        $max_sequence = $previous_sequence_number ? max($previous_sequence_number) : 1;

        foreach ($new_schema as &$column) {
            ++$max_sequence;

            if (Arr::has($column, 'sequence_number') && is_numeric($column['sequence_number'])) {
                $column['sequence_number'] = $max_sequence;
            }

            if (isset($column['id'])) {
                $column['id'] = substr_replace($column['id'], (string) $max_sequence, strrpos($column['id'], ':') + 1);
            }
        }

        return $new_schema;
    }

    /**
     * Reorders the sequence_number of the schema based on the current sequence_number values.
     * If two columns have the same sequence_number, their original order is preserved.
     * Columns with no sequence_number are placed at the end of the schema.
     * The sequence_number values are then reassigned in ascending order starting from 1.
     *
     * @param array $old_schema The schema to reorder.
     *
     * @return array The reordered schema.
     */
    public static function reorder_schema_sequence_number(array $old_schema)
    {
        uasort($old_schema, function ($a, $b) {
            $seqA = (Arr::has($a, 'sequence_number') && is_numeric($a['sequence_number'])) ? $a['sequence_number'] : PHP_INT_MAX;
            $seqB = (Arr::has($b, 'sequence_number') && is_numeric($b['sequence_number'])) ? $b['sequence_number'] : PHP_INT_MAX;
            if ($seqA == $seqB) {
                return 0;
            }
            return ($seqA < $seqB) ? -1 : 1;
        });

        // reorder the sequence number and id attribute
        $newSequence = 1;
        foreach ($old_schema as &$column) {
            if (Arr::has($column, 'sequence_number') && is_numeric($column['sequence_number'])) {
                $column['sequence_number'] = $newSequence;
            }

            $newSequence++;
        }
        unset($column);

        return $old_schema;
    }
}
