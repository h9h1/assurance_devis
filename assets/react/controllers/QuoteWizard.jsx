import React, { useState } from 'react';

export default function QuoteWizard() {
    const [currentStep, setCurrentStep] = useState(1);
    const [formData, setFormData] = useState({
        // Personal info
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        // Driver info
        birthDate: '',
        licenseDate: '',
        // Insurance info
        currentInsurance: '',
        claims: '',
        // Vehicle info
        vehicleType: '',
        brand: '',
        model: '',
        year: '',
        // Summary
    });

    const steps = [
        { number: 1, label: 'Personnel' },
        { number: 2, label: 'Conducteur' },
        { number: 3, label: 'Assurance' },
        { number: 4, label: 'Véhicule' },
        { number: 5, label: 'Résumé' },
    ];

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const nextStep = () => {
        if (currentStep < steps.length) {
            setCurrentStep(currentStep + 1);
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            setCurrentStep(currentStep - 1);
        }
    };

    const renderStepContent = () => {
        switch (currentStep) {
            case 1:
                return (
                    <div className="step-content">
                        <h3>Informations personnelles</h3>
                        <div className="form-group">
                            <label htmlFor="firstName">Prénom</label>
                            <input
                                type="text"
                                id="firstName"
                                name="firstName"
                                value={formData.firstName}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="lastName">Nom</label>
                            <input
                                type="text"
                                id="lastName"
                                name="lastName"
                                value={formData.lastName}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value={formData.email}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="phone">Téléphone</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value={formData.phone}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                    </div>
                );
            case 2:
                return (
                    <div className="step-content">
                        <h3>Informations du conducteur</h3>
                        <div className="form-group">
                            <label htmlFor="birthDate">Date de naissance</label>
                            <input
                                type="date"
                                id="birthDate"
                                name="birthDate"
                                value={formData.birthDate}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="licenseDate">Date d'obtention du permis</label>
                            <input
                                type="date"
                                id="licenseDate"
                                name="licenseDate"
                                value={formData.licenseDate}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                    </div>
                );
            case 3:
                return (
                    <div className="step-content">
                        <h3>Informations d'assurance</h3>
                        <div className="form-group">
                            <label htmlFor="currentInsurance">Assureur actuel</label>
                            <input
                                type="text"
                                id="currentInsurance"
                                name="currentInsurance"
                                value={formData.currentInsurance}
                                onChange={handleInputChange}
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="claims">Nombre de sinistres (3 dernières années)</label>
                            <select
                                id="claims"
                                name="claims"
                                value={formData.claims}
                                onChange={handleInputChange}
                            >
                                <option value="">Sélectionnez</option>
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3+">3 ou plus</option>
                            </select>
                        </div>
                    </div>
                );
            case 4:
                return (
                    <div className="step-content">
                        <h3>Informations du véhicule</h3>
                        <div className="form-group">
                            <label htmlFor="vehicleType">Type de véhicule</label>
                            <select
                                id="vehicleType"
                                name="vehicleType"
                                value={formData.vehicleType}
                                onChange={handleInputChange}
                                required
                            >
                                <option value="">Sélectionnez</option>
                                <option value="car">Voiture</option>
                                <option value="motorcycle">Moto</option>
                            </select>
                        </div>
                        <div className="form-group">
                            <label htmlFor="brand">Marque</label>
                            <input
                                type="text"
                                id="brand"
                                name="brand"
                                value={formData.brand}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="model">Modèle</label>
                            <input
                                type="text"
                                id="model"
                                name="model"
                                value={formData.model}
                                onChange={handleInputChange}
                                required
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="year">Année</label>
                            <input
                                type="number"
                                id="year"
                                name="year"
                                value={formData.year}
                                onChange={handleInputChange}
                                min="1900"
                                max={new Date().getFullYear() + 1}
                                required
                            />
                        </div>
                    </div>
                );
            case 5:
                return (
                    <div className="step-content">
                        <h3>Résumé de votre demande</h3>
                        <div className="summary">
                            <div className="summary-section">
                                <h4>Informations personnelles</h4>
                                <p><strong>Nom:</strong> {formData.firstName} {formData.lastName}</p>
                                <p><strong>Email:</strong> {formData.email}</p>
                                <p><strong>Téléphone:</strong> {formData.phone}</p>
                            </div>
                            <div className="summary-section">
                                <h4>Conducteur</h4>
                                <p><strong>Date de naissance:</strong> {formData.birthDate}</p>
                                <p><strong>Permis depuis:</strong> {formData.licenseDate}</p>
                            </div>
                            <div className="summary-section">
                                <h4>Assurance</h4>
                                <p><strong>Assureur actuel:</strong> {formData.currentInsurance || 'Aucun'}</p>
                                <p><strong>Sinistres:</strong> {formData.claims || '0'}</p>
                            </div>
                            <div className="summary-section">
                                <h4>Véhicule</h4>
                                <p><strong>Type:</strong> {formData.vehicleType === 'car' ? 'Voiture' : 'Moto'}</p>
                                <p><strong>Marque/Modèle:</strong> {formData.brand} {formData.model}</p>
                                <p><strong>Année:</strong> {formData.year}</p>
                            </div>
                        </div>
                    </div>
                );
            default:
                return null;
        }
    };

    const progressPercentage = ((currentStep - 1) / (steps.length - 1)) * 100;

    return (
        <div className="quote-wizard">
            {/* Progress Bar */}
            <div className="wizard-progress">
                <div className="progress-bar">
                    <div
                        className="progress-fill"
                        style={{ width: `${progressPercentage}%` }}
                    ></div>
                </div>
                <div className="progress-steps">
                    {steps.map(step => (
                        <div
                            key={step.number}
                            className={`progress-step ${step.number === currentStep ? 'is-active' : ''} ${step.number < currentStep ? 'is-completed' : ''}`}
                        >
                            <div className="step-number">{step.number}</div>
                            <div className="step-label">{step.label}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Step Content */}
            <div className="wizard-content">
                {renderStepContent()}
            </div>

            {/* Navigation */}
            <div className="wizard-navigation">
                {currentStep > 1 && (
                    <button type="button" className="btn btn-secondary" onClick={prevStep}>
                        Précédent
                    </button>
                )}
                {currentStep < steps.length ? (
                    <button type="button" className="btn btn-primary" onClick={nextStep}>
                        Suivant
                    </button>
                ) : (
                    <button type="submit" className="btn btn-primary">
                        Demander un devis
                    </button>
                )}
            </div>
        </div>
    );
}