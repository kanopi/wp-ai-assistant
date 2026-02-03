module.exports = {
  root: true,
  env: {
    browser: true,
    es2020: true,
    jquery: true,
  },
  extends: ['eslint:recommended'],
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'script',
  },
  rules: {
    // Code quality
    'no-unused-vars': [
      'error',
      {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
      },
    ],
    'no-console': ['warn', { allow: ['warn', 'error'] }],
    'prefer-const': 'error',
    'no-var': 'error',
    'eqeqeq': ['error', 'always'],
    'curly': ['error', 'all'],

    // Best practices
    'no-eval': 'error',
    'no-implied-eval': 'error',
    'no-new-func': 'error',
    'no-script-url': 'error',
    'no-with': 'error',

    // WordPress-specific
    'camelcase': [
      'warn',
      {
        properties: 'never',
        ignoreDestructuring: true,
        ignoreImports: true,
      },
    ],
  },
  globals: {
    wp: 'readonly',
    wpAiAssistant: 'readonly',
    wpAiSearch: 'readonly',
  },
  ignorePatterns: ['node_modules', 'vendor', 'coverage', 'dist', 'indexer'],
};
