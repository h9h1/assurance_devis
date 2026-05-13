document.addEventListener('DOMContentLoaded', () => {
    const wizard = document.querySelector('.js-quote-wizard');
    if (!wizard) return;

    const steps = Array.from(wizard.querySelectorAll('.wizard-step'));
    const indicators = Array.from(wizard.querySelectorAll('[data-step-indicator]'));
    const progressBar = wizard.querySelector('[data-progress-bar]');
    const prevButton = wizard.querySelector('[data-prev]');
    const nextButton = wizard.querySelector('[data-next]');
    const submitButton = wizard.querySelector('[data-submit]');
    const summaryContainer = wizard.querySelector('[data-summary]');
    const insuranceInputs = wizard.querySelectorAll('input[name="insuranceType"]');
    const autoField = wizard.querySelector('.js-auto-field');
    const motoField = wizard.querySelector('.js-moto-field');

    let currentStep = 1;

    const setActiveStep = (step) => {
        currentStep = step;

        steps.forEach((section) => {
            section.classList.toggle('is-active', Number(section.dataset.step) === step);
        });

        indicators.forEach((indicator) => {
            indicator.classList.toggle('is-active', Number(indicator.dataset.stepIndicator) === step);
        });

        const progress = ((step - 1) / (steps.length - 1)) * 100;
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        prevButton.style.visibility = step === 1 ? 'hidden' : 'visible';
        nextButton.style.display = step === steps.length ? 'none' : 'inline-flex';
        submitButton.style.display = step === steps.length ? 'inline-flex' : 'none';

        if (step === 5) {
            renderSummary();
        }
    };

    const getInsuranceType = () => wizard.querySelector('input[name="insuranceType"]:checked')?.value || 'auto';

    const toggleVehicleConditionalFields = () => {
        const type = getInsuranceType();
        const fiscalInput = wizard.querySelector('#fiscalPower');
        const engineInput = wizard.querySelector('#engineCapacity');

        if (autoField) autoField.style.display = type === 'auto' ? 'block' : 'none';
        if (motoField) motoField.style.display = type === 'moto' ? 'block' : 'none';

        if (fiscalInput) {
            fiscalInput.required = type === 'auto';
            if (type !== 'auto') fiscalInput.value = '';
        }

        if (engineInput) {
            engineInput.required = type === 'moto';
            if (type !== 'moto') engineInput.value = '';
        }

        wizard.querySelectorAll('.choice-card').forEach((card) => {
            const checked = card.querySelector('input')?.checked;
            card.classList.toggle('is-selected', Boolean(checked));
        });
    };

    const fieldsByStep = {
        1: ['lastName', 'firstName', 'city', 'phoneNumber'],
        2: ['birthDate', 'licenseDate'],
        3: ['insuranceType'],
        4: ['vehicleBrand', 'fuelType', 'firstRegistrationDate', 'seatCount', 'newValue', 'marketValue', 'registrationNumber'],
    };

    const validateCurrentStep = () => {
        const fieldNames = [...fieldsByStep[currentStep] || []];
        const type = getInsuranceType();

        if (currentStep === 4) {
            fieldNames.push(type === 'auto' ? 'fiscalPower' : 'engineCapacity');
        }

        for (const fieldName of fieldNames) {
            const field = wizard.querySelector(`[name="${fieldName}"]`);
            if (!field) continue;

            if (field.type === 'radio') {
                const checked = wizard.querySelector(`[name="${fieldName}"]:checked`);
                if (!checked) {
                    alert('Veuillez sélectionner un type d\'assurance.');
                    return false;
                }
                continue;
            }

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        return true;
    };

    const renderSummary = () => {
        if (!summaryContainer) return;

        const value = (name) => wizard.querySelector(`[name="${name}"]`)?.value || '-';
        const insuranceType = getInsuranceType();

        summaryContainer.innerHTML = `
            <div class="summary-section">
                <h3>Informations personnelles</h3>
                <ul>
                    <li><strong>Nom :</strong> ${value('lastName')}</li>
                    <li><strong>Prénom :</strong> ${value('firstName')}</li>
                    <li><strong>Ville :</strong> ${value('city')}</li>
                    <li><strong>Téléphone :</strong> ${value('phoneNumber')}</li>
                </ul>
            </div>
            <div class="summary-section">
                <h3>Informations conducteur</h3>
                <ul>
                    <li><strong>Date de naissance :</strong> ${value('birthDate')}</li>
                    <li><strong>Date du permis :</strong> ${value('licenseDate')}</li>
                </ul>
            </div>
            <div class="summary-section">
                <h3>Type d'assurance</h3>
                <ul>
                    <li><strong>Type :</strong> ${insuranceType.toUpperCase()}</li>
                </ul>
            </div>
            <div class="summary-section">
                <h3>Informations véhicule</h3>
                <ul>
                    <li><strong>Marque :</strong> ${value('vehicleBrand')}</li>
                    <li><strong>Carburant :</strong> ${value('fuelType')}</li>
                    <li><strong>Mise en circulation :</strong> ${value('firstRegistrationDate')}</li>
                    <li><strong>Nombre de places :</strong> ${value('seatCount')}</li>
                    <li><strong>Valeur à neuf :</strong> ${value('newValue')} MAD</li>
                    <li><strong>Valeur vénale :</strong> ${value('marketValue')} MAD</li>
                    <li><strong>Immatriculation :</strong> ${value('registrationNumber')}</li>
                    ${insuranceType === 'auto'
                        ? `<li><strong>Puissance fiscale :</strong> ${value('fiscalPower')}</li>`
                        : `<li><strong>Cylindrée :</strong> ${value('engineCapacity')}</li>`}
                </ul>
            </div>
        `;
    };

    nextButton.addEventListener('click', () => {
        if (!validateCurrentStep()) {
            return;
        }

        if (currentStep < steps.length) {
            setActiveStep(currentStep + 1);
        }
    });

    prevButton.addEventListener('click', () => {
        if (currentStep > 1) {
            setActiveStep(currentStep - 1);
        }
    });

    insuranceInputs.forEach((input) => {
        input.addEventListener('change', toggleVehicleConditionalFields);
    });

    indicators.forEach((indicator) => {
        indicator.addEventListener('click', () => {
            const targetStep = Number(indicator.dataset.stepIndicator);
            if (targetStep <= currentStep || validateCurrentStep()) {
                setActiveStep(targetStep);
            }
        });
    });

    wizard.addEventListener('submit', (event) => {
        if (!validateCurrentStep()) {
            event.preventDefault();
        }
    });

    toggleVehicleConditionalFields();
    setActiveStep(1);
});
