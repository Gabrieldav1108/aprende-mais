---
paths:
  - 'app/**'
---

# Validation

## Validate CPF and CNPJ with the App\Rules\CpfRule and App\Rules\CnpjRule rules
Every CPF or CNPJ field must be validated with `new App\Rules\CpfRule` or `new App\Rules\CnpjRule`, never with a hand-written regex or `digits` rule. Each rule accepts only the plain digits or the full mask (`000.000.000-00`, `00.000.000/0000-00`), rejects repeated digits and checks the check digits, failing with a format message or an invalid-document message. A field that takes either document (such as `cpf_cnpj` in PersonValidationRules) picks the rule by length: up to 11 digits is a CPF, otherwise a CNPJ. Code outside validation (such as CnpjService) checks digits with the static `CpfRule::isValid()` / `CnpjRule::isValid()` instead of repeating the algorithm.
