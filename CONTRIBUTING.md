# Contributing to NeoNotion

Thank you for your interest in contributing to **NeoNotion**! We are building an open, sovereign business operating system to empower cross-border organizations and modern teams.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## How Can I Contribute?

### Reporting Bugs
Before creating bug reports, please check existing issues to ensure the issue hasn't been reported. When submitting a bug report:
- Use the **Bug Report** template.
- Provide a clear, descriptive title.
- Detail the exact steps to reproduce the behavior.
- Include system details (OS, browser, Node.js version).

### Suggesting Enhancements
Feature requests are tracked as GitHub issues. When suggesting enhancements:
- Use the **Feature Request** template.
- Explain why this enhancement would be useful to most NeoNotion users.
- Outline possible architectural implementations.

### Pull Requests
1. Fork the repo and create your branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Make your changes adhering to existing architectural standards.
4. Verify tests and linting pass:
   ```bash
   npm run lint
   npm run build
   ```
5. Commit your changes using conventional commit messages (`feat: ...`, `fix: ...`, `docs: ...`, `refactor: ...`).
6. Push to your fork and submit a Pull Request to `main`.

## Coding Standards
- **TypeScript**: Strict type-checking enabled (`noImplicitAny`, proper interfaces).
- **Styling**: Tailwind CSS utility classes; avoid inline styles.
- **Privacy & Data Sovereignty**: Never add code that leaks customer data or transmits user analytics without explicit consent.
- **Components**: Functional React components with hooks.

## Questions & Contact
For questions regarding the project roadmap, contact Pourya Omidian at [itispourya@gmail.com](mailto:itispourya@gmail.com) or visit [NeoNotion.net](https://neonotion.net).
